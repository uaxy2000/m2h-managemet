<?php

namespace App\Http\Controllers;

use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    public function __construct(private GoogleCalendarService $gcal) {}

    public function redirect()
    {
        return redirect($this->gcal->getAuthUrl());
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('profile.edit')->with('error', 'Google Calendar connection cancelled.');
        }

        $token = $this->gcal->exchangeCode($request->input('code'));

        if (isset($token['error'])) {
            return redirect()->route('profile.edit')->with('error', 'Failed to connect Google Calendar.');
        }

        $user = auth()->user();
        $user->update([
            'google_refresh_token' => isset($token['refresh_token']) ? encrypt($token['refresh_token']) : $user->google_refresh_token,
            'google_connected_at'  => now(),
        ]);

        // Get the email from the token's id_token or by making a userinfo request
        if (isset($token['id_token'])) {
            $payload = json_decode(base64_decode(explode('.', $token['id_token'])[1]), true);
            $user->update(['google_email' => $payload['email'] ?? null]);
        }

        $synced = $this->syncUpcomingMeetings($user);

        return redirect()->route('profile.edit')->with('success', 'Google Calendar connected successfully.'
            . ($synced ? " {$synced} upcoming meeting(s) synced to your calendar." : ''));
    }

    // After (re)connecting — possibly to a different Google account — make sure upcoming meetings exist in this calendar.
    private function syncUpcomingMeetings(User $user): int
    {
        $participants = MeetingParticipant::where('participant_type', 'internal_user')
            ->where('participant_id', $user->id)
            ->whereHas('meeting', fn ($q) => $q->where('end_at', '>=', now())
                ->where(fn ($q) => $q->whereNull('ical_status')->orWhere('ical_status', '!=', 'CANCELLED')))
            ->with('meeting')
            ->get();

        $synced = 0;
        foreach ($participants as $participant) {
            $eventId = $this->gcal->upsertForUser($user, $participant->meeting, $participant->google_event_id);
            if ($eventId) {
                $synced++;
                if ($eventId !== $participant->google_event_id) {
                    $participant->update(['google_event_id' => $eventId]);
                }
            }
        }

        return $synced;
    }

    public function disconnect()
    {
        auth()->user()->update([
            'google_email'         => null,
            'google_refresh_token' => null,
            'google_calendar_id'   => 'primary',
            'google_connected_at'  => null,
        ]);

        return redirect()->route('profile.edit')->with('success', 'Google Calendar disconnected.');
    }
}
