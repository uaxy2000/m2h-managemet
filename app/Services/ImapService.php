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

        $this->mailbox  = '{' . $host . ':' . $port . $flags . '}';
        $this->connection = @imap_open($this->mailbox . 'INBOX', $username, $password, 0, 1);

        return $this->connection !== false;
    }

    public function lastError(): string
    {
        return imap_last_error() ?: 'Unknown IMAP error';
    }

    /** Returns raw folder names as returned by the server */
    public function listFolders(): array
    {
        $list = imap_list($this->connection, $this->mailbox, '*');
        if (!$list) return [];
        return array_map(fn ($f) => str_replace($this->mailbox, '', $f), $list);
    }

    /** Detect the Sent folder — first by \Sent attribute, then by name */
    public function detectSentFolder(): ?string
    {
        // Use getmailboxes to read folder attributes (\Sent flag)
        $boxes = @imap_getmailboxes($this->connection, $this->mailbox, '*');
        if ($boxes) {
            foreach ($boxes as $box) {
                if ($box->attributes & LATT_NOINFERIORS) continue; // skip virtual
                // IMAP \Sent attribute = 64 (0x40) but PHP doesn't expose it directly
                // so fall back to name matching below
            }
        }

        // Name-based detection (raw IMAP names, including UTF-7 encoded)
        $rawFolders = $this->listFolders();
        $nameCandidates = [
            'Sent', 'Sent Items', 'Sent Messages',
            '[Gmail]/Sent Mail', 'INBOX.Sent',
            // Decoded Turkish
            'Gönderilen',
        ];

        foreach ($rawFolders as $raw) {
            $decoded = @imap_utf7_decode($raw) ?: $raw;
            foreach ($nameCandidates as $candidate) {
                if (strcasecmp(trim($decoded, '/'), $candidate) === 0) return $raw;
                if (strcasecmp(trim($raw, '/'), $candidate) === 0) return $raw;
            }
            // Generic: if folder name contains 'sent' in any language variation
            if (stripos($decoded, 'sent') !== false || stripos($decoded, 'nderilen') !== false) {
                return $raw;
            }
        }

        return null;
    }

    /**
     * Fetch emails from a folder since a given date.
     * Returns array of parsed email data.
     */
    public function fetchEmails(string $folderRaw, ?Carbon $since = null): array
    {
        $fullFolder = $this->mailbox . $folderRaw;
        if (!@imap_reopen($this->connection, $fullFolder)) {
            return [];
        }

        // Use a date 1 day earlier to avoid timezone boundary issues
        $searchSince = ($since ?? now()->subDays(7))->subDay();
        $criteria    = 'SINCE "' . $searchSince->format('d-M-Y') . '"';

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
        $headers = @imap_rfc822_parse_headers($raw);
        if (!$headers) return null;

        $messageId = trim($headers->message_id ?? '');
        if (empty($messageId)) {
            $messageId = 'uid-' . $uid . '-' . ($headers->date ?? uniqid());
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
        return array_values(array_filter(array_map(function ($addr) {
            $email = trim(($addr->mailbox ?? '') . '@' . ($addr->host ?? ''));
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
        }, $list)));
    }

    private function decodeHeader(string $value): string
    {
        $parts = @imap_mime_header_decode($value);
        if (!$parts) return $value;
        return implode('', array_map(fn ($p) => $p->text, $parts));
    }

    public function close(): void
    {
        if ($this->connection) {
            @imap_close($this->connection);
            $this->connection = null;
        }
    }
}
