<?php

namespace App\Services;

use Carbon\Carbon;

class IcsParserService
{
    public function parse(string $content): ?array
    {
        // Unfold folded lines (RFC 5545 §3.1)
        $content = preg_replace('/\r\n[ \t]/', '', $content);
        $content = preg_replace('/\r?\n[ \t]/', '', $content);

        // VCALENDAR METHOD (REQUEST / CANCEL / REPLY)
        $method = 'REQUEST';
        if (preg_match('/^METHOD:(.+)$/im', $content, $m)) {
            $method = strtoupper(trim($m[1]));
        }

        // Extract first VEVENT block
        if (!preg_match('/BEGIN:VEVENT(.*?)END:VEVENT/si', $content, $m)) {
            return null;
        }
        $event = $m[1];

        $uid      = $this->prop($event, 'UID');
        $sequence = (int) ($this->prop($event, 'SEQUENCE') ?? 0);
        $status   = strtoupper($this->prop($event, 'STATUS') ?? 'CONFIRMED');
        $summary  = $this->unescape($this->prop($event, 'SUMMARY') ?? '');
        $desc     = $this->unescape($this->prop($event, 'DESCRIPTION') ?? '');
        $location = $this->unescape($this->prop($event, 'LOCATION') ?? '');

        if (!$uid) return null;

        $start = $this->parseDateTime($event, 'DTSTART');
        $end   = $this->parseDateTime($event, 'DTEND');
        if (!$start) return null;
        if (!$end) $end = $start->copy()->addHour();

        // ORGANIZER: may be "ORGANIZER;CN=Name:mailto:email" or "ORGANIZER:mailto:email"
        $organizer = null;
        if (preg_match('/^ORGANIZER(?:;[^:]+)?:(?:mailto:)?(.+)$/im', $event, $m)) {
            $organizer = trim($m[1]);
        }

        return [
            'method'      => $method,
            'uid'         => trim($uid),
            'sequence'    => $sequence,
            'status'      => $status,
            'summary'     => $summary,
            'description' => $desc ?: null,
            'location'    => $location ?: null,
            'start'       => $start,
            'end'         => $end,
            'organizer'   => $organizer,
        ];
    }

    private function prop(string $event, string $name): ?string
    {
        if (preg_match('/^' . preg_quote($name, '/') . '(?:;[^:]+)?:(.+)$/im', $event, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function parseDateTime(string $event, string $name): ?Carbon
    {
        // Matches: DTSTART;TZID=Europe/Istanbul:20261014T103000 or DTSTART:20261014T073000Z
        if (!preg_match('/^' . preg_quote($name, '/') . '(?:;TZID=([^;:]+))?(?:;[^:]+)*:(.+)$/im', $event, $m)) {
            return null;
        }

        $tzid  = trim($m[1] ?? '');
        $value = trim($m[2]);

        try {
            // DATE only (no time): YYYYMMDD
            if (strlen($value) === 8 && ctype_digit($value)) {
                $tz = $tzid ?: config('app.timezone', 'UTC');
                return Carbon::createFromFormat('Ymd', $value, $tz)
                    ?->startOfDay()
                    ?->setTimezone(config('app.timezone', 'UTC'));
            }

            $isUtc = str_ends_with($value, 'Z');
            $value = rtrim($value, 'Z');
            $tz    = $isUtc ? 'UTC' : ($tzid ?: config('app.timezone', 'UTC'));

            return Carbon::createFromFormat('Ymd\THis', $value, $tz)
                ?->setTimezone(config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function unescape(string $value): string
    {
        return str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $value);
    }
}
