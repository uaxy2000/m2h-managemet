<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Setting;
use App\Models\User;
use App\Services\MeetingImportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailSyncService
{
    // Plesk/IIS ends a request well before PHP's own limit; stop early and continue on the next run
    private const TIME_BUDGET_SECONDS = 40;

    private float $deadline = 0;

    /** Lowercased lead email => true, so messages can be skipped from their headers alone */
    private array $leadEmails = [];

    public function __construct(private ImapService $imap) {}

    /** Sync all configured accounts. Returns summary array. */
    public function syncAll(): array
    {
        @set_time_limit(120);
        $this->deadline   = microtime(true) + self::TIME_BUDGET_SECONDS;
        $this->leadEmails = Lead::whereNotNull('email')->pluck('email')
            ->mapWithKeys(fn ($e) => [strtolower(trim($e)) => true])
            ->all();

        $accounts = [];

        // 1. Shared info@ account
        if (Setting::get('imap_enabled') === '1') {
            $accounts['shared'] = [
                'label'      => Setting::get('imap_username', ''),
                'host'       => Setting::get('imap_host', ''),
                'port'       => (int) Setting::get('imap_port', '993'),
                'encryption' => Setting::get('imap_encryption', 'ssl'),
                'username'   => Setting::get('imap_username', ''),
                'password'   => $this->decryptPassword(Setting::get('imap_password')),
                'since'      => Setting::get('imap_last_sync_at') ? Carbon::parse(Setting::get('imap_last_sync_at')) : now()->subDays(7),
                'onSuccess'  => fn () => Setting::set('imap_last_sync_at', now()->toISOString()),
            ];
        }

        // 2. Per-user accounts
        $users = User::where('imap_enabled', true)->whereNotNull('imap_host')->get();
        foreach ($users as $user) {
            $accounts['user_' . $user->id] = [
                'label'      => $user->email,
                'host'       => $user->imap_host,
                'port'       => $user->imap_port ?? 993,
                'encryption' => $user->imap_encryption ?? 'ssl',
                'username'   => $user->imap_user,
                'password'   => $this->decryptPassword($user->imap_pass_enc),
                'since'      => $user->imap_last_sync_at ? Carbon::parse($user->imap_last_sync_at) : now()->subDays(7),
                'onSuccess'  => fn () => $user->update(['imap_last_sync_at' => now()]),
            ];
        }

        // Least recently synced first, so one slow mailbox cannot starve the others
        uasort($accounts, fn ($a, $b) => $a['since'] <=> $b['since']);

        $results = [];
        foreach ($accounts as $key => $account) {
            $results[$key] = $this->outOfTime()
                ? ['status' => 'deferred', 'reason' => 'time budget reached, continues on next run']
                : $this->syncAccount(...$account);
        }

        return $results;
    }

    private function outOfTime(): bool
    {
        return microtime(true) >= $this->deadline;
    }

    private function syncAccount(
        string $label,
        string $host,
        int $port,
        string $encryption,
        string $username,
        ?string $password,
        Carbon $since,
        callable $onSuccess,
    ): array {
        if (!$host || !$username || !$password) {
            return ['status' => 'skipped', 'reason' => 'incomplete config'];
        }

        try {
            if (!$this->imap->connect($host, $port, $encryption, $username, $password)) {
                return ['status' => 'error', 'error' => $this->imap->lastError()];
            }

            $synced  = 0;
            $skipped = 0;

            // INBOX → email_in, Sent folder → email_out
            $finished   = $this->syncFolder('INBOX', 'email_in', $host, $username, $since, $synced, $skipped);
            $sentFolder = $finished ? $this->imap->detectSentFolder() : null;
            if ($sentFolder) {
                $finished = $this->syncFolder($sentFolder, 'email_out', $host, $username, $since, $synced, $skipped);
            }

            $this->imap->close();
            if ($finished) {
                $onSuccess();
            }

            return ['status' => $finished ? 'ok' : 'partial', 'synced' => $synced, 'skipped' => $skipped];
        } catch (\Throwable $e) {
            $prev = $e->getPrevious();
            Log::error("IMAP sync failed for {$label}: " . $e->getMessage(), [
                'exception'  => get_class($e),
                'file'       => $e->getFile() . ':' . $e->getLine(),
                'previous'   => $prev ? get_class($prev) . ': ' . $prev->getMessage() . ' @ ' . $prev->getFile() . ':' . $prev->getLine() : null,
                'trace'      => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
            ]);
            $this->imap->close();
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Process new messages of one folder. The last processed UID is remembered per mailbox/folder,
     * so each run only looks at mail that arrived since the previous one, even if that run was cut short.
     * Returns false when the time budget ran out before the folder was finished.
     */
    private function syncFolder(string $folder, string $type, string $host, string $username, Carbon $since, int &$synced, int &$skipped): bool
    {
        $uidValidity = $this->imap->openFolder($folder);
        if ($uidValidity === null) {
            return true;
        }

        $key = 'imap_uid:' . md5(strtolower($username) . '|' . $host . '|' . $folder);
        [$savedValidity, $lastUid] = array_map('intval', explode(':', Setting::get($key, '0:0')) + [0, 0]);
        if ($savedValidity !== $uidValidity) {
            $lastUid = 0; // folder was recreated on the server, old UIDs are meaningless
        }

        $finished = true;
        foreach ($this->imap->searchSince($since) as $uid) {
            if ($uid <= $lastUid) continue;
            if ($this->outOfTime()) { $finished = false; break; }

            $email = $this->imap->fetchHeader($uid);
            if ($email) {
                ['created' => $c, 'skipped' => $s] = $this->processEmail($uid, $email, $type, $username);
                $synced += $c; $skipped += $s;
            }
            $lastUid = $uid;
        }

        Setting::set($key, $uidValidity . ':' . $lastUid);

        return $finished;
    }

    private function processEmail(int $uid, array $email, string $type, string $accountEmail): array
    {
        // Collect all addresses involved
        $allAddresses = array_values(array_unique(array_merge(
            $email['from'],
            $email['to'],
            $email['cc'],
            $email['bcc'],
        )));

        $leadAddresses = array_values(array_filter($allAddresses, fn ($a) => isset($this->leadEmails[$a])));
        if (!$leadAddresses) return ['created' => 0, 'skipped' => 1];

        // Deduplicate by message_id per lead before downloading the body
        $leads = Lead::whereIn('email', $leadAddresses)->get();
        $alreadyLinked = LeadActivity::whereIn('lead_id', $leads->pluck('id'))
            ->whereIn('type', ['email_in', 'email_out'])
            ->where('imap_message_id', $email['message_id'])
            ->pluck('lead_id')
            ->all();
        $leads = $leads->reject(fn ($lead) => in_array($lead->id, $alreadyLinked, true));
        if ($leads->isEmpty()) return ['created' => 0, 'skipped' => 0];

        $email += $this->imap->fetchContent($uid);

        $created      = 0;
        $icsProcessed = false;

        foreach ($leads as $lead) {
            $activity = LeadActivity::create([
                'lead_id'          => $lead->id,
                'user_id'          => null,
                'type'             => $type,
                'description'      => $email['subject'],
                'imap_message_id'  => $email['message_id'],
                'created_at'       => $email['date'],
                'meta'             => [
                    'message_id'  => $email['message_id'],
                    'from'        => $email['from'][0] ?? $accountEmail,
                    'to'          => $email['to'],
                    'cc'          => $email['cc'],
                    'subject'     => $email['subject'],
                    'body'        => $email['body'] ?? '',
                    'attachments' => $email['attachments'] ?? [],
                    'synced_from' => $accountEmail,
                    'via_imap'    => true,
                ],
            ]);
            $created++;

            // Process ICS once per email (first matching lead)
            if (!$icsProcessed && !empty($email['ics_content'])) {
                try {
                    app(MeetingImportService::class)->processIcs(
                        icsContent:     $email['ics_content'],
                        leadId:         $lead->id,
                        emailActivityId:$activity->id,
                        emailAddresses: $allAddresses,
                    );
                } catch (\Throwable $e) {
                    $prev = $e->getPrevious();
                    Log::warning('MeetingImport failed', [
                        'error'    => $e->getMessage(),
                        'class'    => get_class($e),
                        'file'     => $e->getFile() . ':' . $e->getLine(),
                        'previous' => $prev ? get_class($prev) . ': ' . $prev->getMessage() : null,
                        'trace'    => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
                    ]);
                }
                $icsProcessed = true;
            }
        }

        return ['created' => $created, 'skipped' => 0];
    }

    private function decryptPassword(?string $encrypted): ?string
    {
        if (!$encrypted) return null;
        try { return decrypt($encrypted); } catch (\Throwable) { return null; }
    }
}
