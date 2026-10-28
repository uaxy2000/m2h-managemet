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

    /**
     * Detect the Sent folder by name. PHP's imap extension does not expose the \Sent
     * special-use flag, and Gmail localises folder names to the mailbox language
     * (e.g. "[Gmail]/Gönderilmiş Postalar").
     */
    public function detectSentFolder(): ?string
    {
        $folders = [];
        foreach ($this->listFolders() as $raw) {
            // Folder names are modified UTF-7; imap_utf7_decode() yields ISO-8859-1 and loses characters like "ş".
            $folders[$raw] = trim(mb_convert_encoding($raw, 'UTF-8', 'UTF7-IMAP'), '/');
        }

        $exact = [
            'Sent', 'Sent Items', 'Sent Messages', 'INBOX.Sent',
            '[Gmail]/Sent Mail', '[Gmail]/Gönderilmiş Postalar', 'Gönderilen',
        ];
        foreach ($folders as $raw => $name) {
            foreach ($exact as $candidate) {
                if (mb_strtolower($name) === mb_strtolower($candidate)) return $raw;
            }
        }

        foreach ($folders as $raw => $name) {
            if (stripos($name, 'sent') !== false || stripos($name, 'nderilen') !== false || stripos($name, 'nderilmi') !== false) {
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

        // Ensure valid UTF-8 — strip invalid bytes silently
        $body = @iconv('UTF-8', 'UTF-8//IGNORE', $body) ?: '';

        return mb_substr($body, 0, $limit);
    }

    /**
     * Return list of attachments [{name, size, mime}] for a message.
     */
    public function fetchAttachments(int $uid): array
    {
        $structure = @imap_fetchstructure($this->connection, $uid, FT_UID);
        if (!$structure || empty($structure->parts)) return [];

        $result = [];
        foreach ($structure->parts as $part) {
            $disposition = strtolower($part->ifdisposition ? $part->disposition : '');
            $name = null;

            // Check dparameters (Content-Disposition params)
            if (!empty($part->dparameters)) {
                foreach ($part->dparameters as $p) {
                    if (strtolower($p->attribute) === 'filename') { $name = $p->value; break; }
                }
            }
            // Fallback: parameters (Content-Type params)
            if (!$name && !empty($part->parameters)) {
                foreach ($part->parameters as $p) {
                    if (strtolower($p->attribute) === 'name') { $name = $p->value; break; }
                }
            }

            if (!$name) continue;
            if ($disposition === 'inline' && $part->type === TYPETEXT) continue; // skip inline text

            $name = $this->decodeHeader($name);
            $name = @iconv('UTF-8', 'UTF-8//IGNORE', $name) ?: $name;

            $result[] = [
                'name' => $name,
                'size' => $part->bytes ?? 0,
                'mime' => ($part->type === TYPEAPPLICATION ? 'application' : imap_mime_header_decode($part->subtype ?? '')[0]->text ?? '') . '/' . strtolower($part->subtype ?? 'octet-stream'),
            ];
        }

        return $result;
    }

    /** Recursively collect all text parts as [{num, encoding, subtype, charset}] */
    private function collectTextParts(object $structure, string $prefix = ''): array
    {
        $parts = [];
        if ($structure->type === TYPEMULTIPART && !empty($structure->parts)) {
            foreach ($structure->parts as $i => $part) {
                $num   = $prefix === '' ? (string)($i + 1) : $prefix . '.' . ($i + 1);
                $parts = array_merge($parts, $this->collectTextParts($part, $num));
            }
        } elseif ($structure->type === TYPETEXT) {
            $charset = 'UTF-8';
            if (!empty($structure->parameters)) {
                foreach ($structure->parameters as $p) {
                    if (strtolower($p->attribute) === 'charset') { $charset = strtoupper($p->value); break; }
                }
            }
            $parts[] = [
                'num'      => $prefix === '' ? '1' : $prefix,
                'encoding' => $structure->encoding ?? ENC7BIT,
                'subtype'  => strtolower($structure->subtype ?? 'plain'),
                'charset'  => $charset,
            ];
        }
        return $parts;
    }

    private function extractPlainText(int $uid, object $structure, string $partNum): string
    {
        $all   = $this->collectTextParts($structure);
        $plain = array_values(array_filter($all, fn ($p) => $p['subtype'] === 'plain'));
        $html  = array_values(array_filter($all, fn ($p) => $p['subtype'] === 'html'));
        $pick  = $plain[0] ?? $html[0] ?? null;
        if (!$pick) return '';

        $raw  = @imap_fetchbody($this->connection, $uid, $pick['num'], FT_UID);
        $text = $this->decodePart($raw ?: '', $pick['encoding']);

        // Convert to UTF-8 if needed
        if ($pick['charset'] !== 'UTF-8' && $pick['charset'] !== 'US-ASCII') {
            $converted = @iconv($pick['charset'], 'UTF-8//IGNORE', $text);
            if ($converted !== false) $text = $converted;
        }

        return $text;
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
     * Return the decoded content of the first text/calendar or .ics part in a message.
     */
    public function fetchIcsContent(int $uid): ?string
    {
        $structure = @imap_fetchstructure($this->connection, $uid, FT_UID);
        if (!$structure) return null;

        return $this->findIcsPart($uid, $structure, '');
    }

    private function findIcsPart(int $uid, object $part, string $prefix): ?string
    {
        if ($this->isIcsPart($part)) {
            $num = $prefix ?: '1';
            $raw = @imap_fetchbody($this->connection, $uid, $num, FT_UID);
            if (!$raw) return null;
            $decoded = $this->decodePart($raw, $part->encoding ?? ENC7BIT);
            // Ensure valid UTF-8
            return @iconv('UTF-8', 'UTF-8//IGNORE', $decoded) ?: $decoded;
        }

        if (($part->type ?? 0) === TYPEMULTIPART && !empty($part->parts)) {
            foreach ($part->parts as $i => $sub) {
                $num    = $prefix === '' ? (string)($i + 1) : $prefix . '.' . ($i + 1);
                $result = $this->findIcsPart($uid, $sub, $num);
                if ($result !== null) return $result;
            }
        }

        return null;
    }

    private function isIcsPart(object $part): bool
    {
        $type    = $part->type ?? 0;
        $subtype = strtolower($part->subtype ?? '');

        if ($type === TYPETEXT && $subtype === 'calendar') return true;
        if ($type === TYPEAPPLICATION && in_array($subtype, ['ics', 'calendar'])) return true;

        foreach (array_merge($part->parameters ?? [], $part->dparameters ?? []) as $p) {
            if (in_array(strtolower($p->attribute), ['name', 'filename'])) {
                if (str_ends_with(strtolower($p->value), '.ics')) return true;
            }
        }

        return false;
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

            try { $parsed['body']        = $this->fetchBody($uid); }        catch (\Throwable) { $parsed['body'] = ''; }
            try { $parsed['attachments'] = $this->fetchAttachments($uid); } catch (\Throwable) { $parsed['attachments'] = []; }
            try { $parsed['ics_content'] = $this->fetchIcsContent($uid); }  catch (\Throwable) { $parsed['ics_content'] = null; }
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
            'date'       => isset($headers->date) ? $this->parseEmailDate($headers->date) : now(),
            'from'       => $this->extractAddresses($headers->from ?? []),
            'to'         => $this->extractAddresses($headers->to ?? []),
            'cc'         => $this->extractAddresses($headers->cc ?? []),
            'bcc'        => $this->extractAddresses($headers->bcc ?? []),
        ];
    }

    private function parseEmailDate(string $raw): \Carbon\Carbon
    {
        // RFC 2822 allows comments in parentheses, e.g. "+0300 (+03)" — strip them
        $clean = preg_replace('/\s*\([^)]*\)/', '', $raw);
        $clean = trim($clean);
        try {
            // Convert to app timezone so MySQL stores local time consistently
            return Carbon::parse($clean)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return now();
        }
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
