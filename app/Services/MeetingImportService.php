<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\MeetingImportLog;
use App\Models\MeetingParticipant;
use App\Models\User;

class MeetingImportService
{
    public function __construct(private IcsParserService $parser) {}

    /**
     * Process an ICS string from an email and create/update/cancel a meeting.
     * Returns the action taken: imported | rescheduled | cancelled | skipped.
     *
     * @param  string[] $emailAddresses  All from/to/cc/bcc addresses from the email
     */
    public function processIcs(
        string  $icsContent,
        ?string $leadId,
        ?string $emailActivityId,
        array   $emailAddresses = [],
    ): string {
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
                'action'            => 'cancelled',
                'meeting_id'        => $existing->id,
                'lead_id'           => $leadId,
                'email_activity_id' => $emailActivityId,
                'ical_uid'          => $uid,
                'title'             => $existing->title,
                'organizer'         => $data['organizer'],
                'old_start'         => $existing->start_at,
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

            // Sync participants if not yet set
            if (!empty($emailAddresses) && $existing->participants()->count() === 0) {
                $this->syncParticipants($existing, $emailAddresses);
            }

            MeetingImportLog::create([
                'action'            => 'rescheduled',
                'meeting_id'        => $existing->id,
                'lead_id'           => $leadId,
                'email_activity_id' => $emailActivityId,
                'ical_uid'          => $uid,
                'title'             => $data['summary'],
                'organizer'         => $data['organizer'],
                'old_start'         => $oldStart,
                'new_start'         => $data['start'],
                'new_end'           => $data['end'],
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
            'ical_uid'      => $uid,
            'ical_sequence' => $sequence,
            'ical_status'   => $data['status'],
        ]);

        if (!empty($emailAddresses)) {
            $this->syncParticipants($meeting, $emailAddresses);
        }

        MeetingImportLog::create([
            'action'            => 'imported',
            'meeting_id'        => $meeting->id,
            'lead_id'           => $leadId,
            'email_activity_id' => $emailActivityId,
            'ical_uid'          => $uid,
            'title'             => $data['summary'],
            'organizer'         => $data['organizer'],
            'new_start'         => $data['start'],
            'new_end'           => $data['end'],
        ]);

        return 'imported';
    }

    /**
     * Find CRM users and leads matching the email addresses and add as participants.
     * - CRM users → internal_user
     * - Leads → lead, plus their assigned user → internal_user
     * Deduplicates: each participant_id is added only once per meeting.
     */
    private function syncParticipants(Meeting $meeting, array $addresses): void
    {
        $addresses = array_map('strtolower', array_filter($addresses));
        if (empty($addresses)) return;

        $addedUserIds = [];
        $addedLeadIds = [];

        // ── CRM users whose email is in the address list ─────────────────────
        $users = User::whereIn('email', $addresses)->get();
        foreach ($users as $user) {
            if (in_array($user->id, $addedUserIds)) continue;
            MeetingParticipant::create([
                'meeting_id'       => $meeting->id,
                'participant_type' => 'internal_user',
                'participant_id'   => $user->id,
                'participant_name' => $user->name,
                'participant_email'=> $user->email,
            ]);
            $addedUserIds[] = $user->id;
        }

        // ── Leads whose email is in the address list ──────────────────────────
        $leads = Lead::whereIn('email', $addresses)->with('assignedTo')->get();
        foreach ($leads as $lead) {
            if (in_array($lead->id, $addedLeadIds)) continue;
            MeetingParticipant::create([
                'meeting_id'       => $meeting->id,
                'participant_type' => 'lead',
                'participant_id'   => $lead->id,
                'participant_name' => trim($lead->first_name . ' ' . $lead->last_name),
                'participant_email'=> $lead->email,
            ]);
            $addedLeadIds[] = $lead->id;

            // Also add the lead's assigned user if not already added
            if ($lead->assignedTo && !in_array($lead->assignedTo->id, $addedUserIds)) {
                MeetingParticipant::create([
                    'meeting_id'       => $meeting->id,
                    'participant_type' => 'internal_user',
                    'participant_id'   => $lead->assignedTo->id,
                    'participant_name' => $lead->assignedTo->name,
                    'participant_email'=> $lead->assignedTo->email,
                ]);
                $addedUserIds[] = $lead->assignedTo->id;
            }
        }
    }
}
