<?php

namespace App\Services;

use Carbon\Carbon;

class ImapService
{
    private mixed $connection = null;
    private string $mailbox;

    public function connect(string $host, int $port, string $encryption, string $username, string $password): bool
    {
        if (!extension_loaded('imap')) {
            throw new \RuntimeException('PHP IMAP extension is not loaded.');
        }

        $flags = match ($encryption) {
            'ssl'  => '/imap/ssl/novalidate-cert',
            'tls'  => '/imap/tls/novalidate-cert',
            default => '/imap/notls',
        };

        $this->mailbox = '{' . $host . ':' . $port . $flags . '}';
        $this->connection = @imap_open($this->mailbox, $username, $password, 0, 1);

        return $this->connection !== false;
    }

    public function lastError(): string
    {
        return imap_last_error() ?: 'Unknown IMAP error';
    }

    /** Returns list of folder names on this account */
    public function listFolders(): array
    {
        $list = imap_list($this->connection, $this->mailbox, '*');
        return $list ? array_map(fn ($f) => imap_utf7_decode(str_replace($this->mailbox, '', $f)), $list) : [];
    }

    /** Detect the Sent folder name (varies by provider) */
    public function detectSentFolder(): ?string
    {
        $candidates = ['Sent', 'Sent Items', 'Sent Messages', '[Gmail]/Sent Mail', 'INBOX.Sent'];
        $folders    = $this->listFolders();
        foreach ($candidates as $c) {
            foreach ($folders as $f) {
                if (strcasecmp(trim($f, '/'), $c) === 0) return trim($f, '/');
            }
        }
        return null;
    }

    /**
     * Fetch emails from a folder since a given date.
     * Returns array of parsed email data.
     */
    public function fetchEmails(string $folder, ?Carbon $since = null): array
    {
        $fullFolder = $this->mailbox . $folder;
        if (!@imap_reopen($this->connection, $fullFolder)) {
            return [];
        }

        $criteria = $since ? 'SINCE "' . $since->format('d-M-Y') . '"' : 'SINCE "01-Jan-2020"';
        $uids = @imap_search($this->connection, $criteria, SE_UID);
        if (!$uids) return [];

        $emails = [];
        foreach ($uids as $uid) {
            $header = @imap_fetchheader($this->connection, $uid, FT_UID);
            if (!$header) continue;

            $parsed = $this->parseHeader($header, $uid);
            if ($parsed) $emails[] = $parsed;
        }

        return $emails;
    }

    private function parseHeader(string $raw, int $uid): ?array
    {
        $headers = imap_rfc822_parse_headers($raw);
        if (!$headers) return null;

        $messageId = trim($headers->message_id ?? '');
        if (empty($messageId)) {
            // Fallback: use uid + date as pseudo-id
            $messageId = 'uid-' . $uid . '-' . ($headers->date ?? '');
        }

        return [
            'message_id' => $messageId,
            'subject'    => $this->decodeHeader($headers->subject ?? '(no subject)'),
            'date'       => isset($headers->date) ? Carbon::parse($headers->date) : now(),
            'from'       => $this->extractAddresses($headers->from ?? []),
            'to'         => $this->extractAddresses($headers->to ?? []),
            'cc'         => $this->extractAddresses($headers->cc ?? []),
            'bcc'        => $this->extractAddresses($headers->bcc ?? []),
        ];
    }

    private function extractAddresses(array $list): array
    {
        return array_filter(array_map(function ($addr) {
            $email = trim(($addr->mailbox ?? '') . '@' . ($addr->host ?? ''));
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
        }, $list));
    }

    private function decodeHeader(string $value): string
    {
        $decoded = imap_mime_header_decode($value);
        return implode('', array_map(fn ($p) => $p->text, $decoded));
    }

    public function close(): void
    {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }
}
