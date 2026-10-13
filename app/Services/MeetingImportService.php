<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingImportLog;
use Illuminate\Support\Facades\Log;

class MeetingImportService
{
    public function __construct(private IcsParserService $parser) {}

    /**
     * Process an ICS string from an email and create/update/cancel a meeting.
     * Returns the action taken: imported | rescheduled | cancelled | skipped.
     */
    public function processIcs(string $icsContent, ?string $leadId, ?string $emailActivityId): string
    {
        $data = $this->parser->parse($icsContent);
        if (!$data) return 'skipped';

        $uid      = $data['uid'];
        $sequence = $data['sequence'];
        $method   = $data['method'];

        $existing = Meeting::where('ical_uid', $uid)->first();

        // ── CANCEL ──────────────────────────────────────────────────────────
        if ($method === 'CANCEL') {
            if (!$existing) return 'skipped';

            $existing->update(['ical_status' => 'CANCELLED']);

            MeetingImportLog::create([
                'action'           => 'cancelled',
                'meeting_id'       => $existing->id,
                'lead_id'          => $leadId,
                'email_activity_id'=> $emailActivityId,
                'ical_uid'         => $uid,
                'title'            => $existing->title,
                'organizer'        => $data['organizer'],
                'old_start'        => $existing->start_at,
                'new_start'        => null,
                'new_end'          => null,
            ]);

            return 'cancelled';
        }

        // ── UPDATE (same UID, higher sequence) ───────────────────────────────
        if ($existing) {
            if ($sequence <= $existing->ical_sequence) return 'skipped';

            $oldStart = $existing->start_at;

            $existing->update([
                'title'         => $data['summary'],
                'start_at'      => $data['start'],
                'end_at'        => $data['end'],
                'is_online'     => true,
                'online_url'    => $data['location'] ?? $existing->online_url,
                'ical_sequence' => $sequence,
                'ical_status'   => $data['status'],
            ]);

            MeetingImportLog::create([
                'action'           => 'rescheduled',
                'meeting_id'       => $existing->id,
                'lead_id'          => $leadId,
                'email_activity_id'=> $emailActivityId,
                'ical_uid'         => $uid,
                'title'            => $data['summary'],
                'organizer'        => $data['organizer'],
                'old_start'        => $oldStart,
                'new_start'        => $data['start'],
                'new_end'          => $data['end'],
            ]);

            return 'rescheduled';
        }

        // ── NEW MEETING ───────────────────────────────────────────────────────
        $meeting = Meeting::create([
            'title'         => $data['summary'],
            'description'   => $data['description'],
            'start_at'      => $data['start'],
            'end_at'        => $data['end'],
            'is_online'     => true,
            'online_url'    => $data['location'],
            'created_by'    => null,
            'ical_uid'      => $uid,
            'ical_sequence' => $sequence,
            'ical_status'   => $data['status'],
        ]);

        MeetingImportLog::create([
            'action'           => 'imported',
            'meeting_id'       => $meeting->id,
            'lead_id'          => $leadId,
            'email_activity_id'=> $emailActivityId,
            'ical_uid'         => $uid,
            'title'            => $data['summary'],
            'organizer'        => $data['organizer'],
            'old_start'        => null,
            'new_start'        => $data['start'],
            'new_end'          => $data['end'],
        ]);

        return 'imported';
    }
}
