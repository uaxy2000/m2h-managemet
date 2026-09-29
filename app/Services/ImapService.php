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
     * Fetch plain-text body of a single message by UID, max $limit chars.
     * Returns empty string if not found or binary-only.
     */
    public function fetchBody(int $uid, int $limit = 3000): string
    {
        $structure = @imap_fetchstructure($this->connection, $uid, FT_UID);
        if (!$structure) return '';

        $body = $this->extractPlainText($uid, $structure, '');
        $body = strip_tags($body);
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/\r\n|\r/', "\n", $body);
        $body = preg_replace('/\n{3,}/', "\n\n", trim($body));

        return mb_substr($body, 0, $limit);
    }

    private function extractPlainText(int $uid, object $structure, string $partNum): string
    {
        // Multipart
        if ($structure->type === TYPEMULTIPART && !empty($structure->parts)) {
            // prefer plain (subtype TEXT/PLAIN) over html
            $plain = null; $html = null;
            foreach ($structure->parts as $i => $part) {
                $num = $partNum === '' ? (string)($i + 1) : $partNum . '.' . ($i + 1);
                if ($part->type === TYPETEXT) {
                    if (strtolower($part->subtype) === 'plain') $plain = $num;
                    if (strtolower($part->subtype) === 'html')  $html  = $num;
                } elseif ($part->type === TYPEMULTIPART) {
                    $nested = $this->extractPlainText($uid, $part, $num);
                    if ($nested !== '') return $nested;
                }
            }
            $target = $plain ?? $html;
            if (!$target) return '';
            $part = $structure->parts[(int)explode('.', $target)[count(explode('.', $target)) - 1] - 1];
            return $this->decodePart(@imap_fetchbody($this->connection, $uid, $target, FT_UID), $part->encoding ?? ENC7BIT);
        }

        // Single part
        if ($structure->type === TYPETEXT) {
            $raw = @imap_fetchbody($this->connection, $uid, $partNum === '' ? '1' : $partNum, FT_UID);
            return $this->decodePart($raw, $structure->encoding ?? ENC7BIT);
        }

        return '';
    }

    private function decodePart(string $raw, int $encoding): string
    {
        return match ($encoding) {
            ENCBASE64          => base64_decode($raw),
            ENCQUOTEDPRINTABLE => quoted_printable_decode($raw),
            default            => $raw,
        };
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
            if (!$parsed) continue;

            $parsed['body'] = $this->fetchBody($uid);
            $emails[] = $parsed;
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
