<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailSyncService
{
    public function __construct(private ImapService $imap) {}

    /** Sync all configured accounts. Returns summary array. */
    public function syncAll(): array
    {
        $results = [];

        // 1. Shared info@ account
        if (Setting::get('imap_enabled') === '1') {
            $results['shared'] = $this->syncAccount(
                label:      Setting::get('imap_username', ''),
                host:       Setting::get('imap_host', ''),
                port:       (int) Setting::get('imap_port', '993'),
                encryption: Setting::get('imap_encryption', 'ssl'),
                username:   Setting::get('imap_username', ''),
                password:   $this->decryptPassword(Setting::get('imap_password')),
                since:      Setting::get('imap_last_sync_at') ? Carbon::parse(Setting::get('imap_last_sync_at')) : now()->subDays(7),
                onSuccess:  fn () => Setting::set('imap_last_sync_at', now()->toISOString()),
            );
        }

        // 2. Per-user accounts
        $users = User::where('imap_enabled', true)->whereNotNull('imap_host')->get();
        foreach ($users as $user) {
            $results['user_' . $user->id] = $this->syncAccount(
                label:      $user->email,
                host:       $user->imap_host,
                port:       $user->imap_port ?? 993,
                encryption: $user->imap_encryption ?? 'ssl',
                username:   $user->imap_user,
                password:   $this->decryptPassword($user->imap_pass_enc),
                since:      $user->imap_last_sync_at ? Carbon::parse($user->imap_last_sync_at) : now()->subDays(7),
                onSuccess:  fn () => $user->update(['imap_last_sync_at' => now()]),
            );
        }

        return $results;
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

            // INBOX → email_in
            $inbox = $this->imap->fetchEmails('INBOX', $since);
            foreach ($inbox as $email) {
                ['created' => $c, 'skipped' => $s] = $this->processEmail($email, 'email_in', $username);
                $synced += $c; $skipped += $s;
            }

            // Sent folder → email_out
            $sentFolder = $this->imap->detectSentFolder();
            if ($sentFolder) {
                $sent = $this->imap->fetchEmails($sentFolder, $since);
                foreach ($sent as $email) {
                    ['created' => $c, 'skipped' => $s] = $this->processEmail($email, 'email_out', $username);
                    $synced += $c; $skipped += $s;
                }
            }

            $this->imap->close();
            $onSuccess();

            return ['status' => 'ok', 'synced' => $synced, 'skipped' => $skipped];
        } catch (\Throwable $e) {
            Log::error("IMAP sync failed for {$label}: " . $e->getMessage());
            $this->imap->close();
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function processEmail(array $email, string $type, string $accountEmail): array
    {
        // Collect all addresses involved
        $allAddresses = array_values(array_unique(array_merge(
            $email['from'],
            $email['to'],
            $email['cc'],
            $email['bcc'],
        )));

        // Find matching leads
        $leads = Lead::whereIn('email', $allAddresses)->get();
        if ($leads->isEmpty()) return ['created' => 0, 'skipped' => 1];

        $created = 0;
        foreach ($leads as $lead) {
            // Deduplicate by message_id per lead (plain column, no JSON query needed)
            $exists = LeadActivity::where('lead_id', $lead->id)
                ->whereIn('type', ['email_in', 'email_out'])
                ->where('imap_message_id', $email['message_id'])
                ->exists();

            if ($exists) continue;

            LeadActivity::create([
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
        }

        return ['created' => $created, 'skipped' => 0];
    }

    private function decryptPassword(?string $encrypted): ?string
    {
        if (!$encrypted) return null;
        try { return decrypt($encrypted); } catch (\Throwable) { return null; }
    }
}
