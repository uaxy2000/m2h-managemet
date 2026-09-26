<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private const TOKEN_URL   = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL  = 'https://oauth2.googleapis.com/revoke';
    private const EVENTS_URL  = 'https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events';
    private const AUTH_URL    = 'https://accounts.google.com/o/oauth2/v2/auth';

    public function getAuthUrl(): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => config('services.google.client_id'),
            'redirect_uri'  => config('services.google.redirect'),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri'  => config('services.google.redirect'),
            'grant_type'    => 'authorization_code',
        ]);

        return $response->json() ?? [];
    }

    private function getAccessToken(User $user): ?string
    {
        try {
            $refreshToken = decrypt($user->google_refresh_token);
        } catch (\Exception $e) {
            return null;
        }

        $response = Http::post(self::TOKEN_URL, [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        return $response->json('access_token');
    }

    public function createForUser(User $user, Meeting $meeting): ?string
    {
        $token = $this->getAccessToken($user);
        if (!$token) return null;

        $calendarId = $user->google_calendar_id ?? 'primary';
        $url = str_replace('{calendarId}', rawurlencode($calendarId), self::EVENTS_URL);

        if ($meeting->is_online) {
            $url .= '?conferenceDataVersion=1';
        }

        $response = Http::withToken($token)->post($url, $this->buildBody($meeting));

        if ($response->successful()) {
            $data = $response->json();

            if ($meeting->is_online && !empty($data['hangoutLink'])) {
                $meeting->update(['online_url' => $data['hangoutLink']]);
            }

            return $data['id'] ?? null;
        }

        Log::error('GoogleCalendar createForUser failed', ['status' => $response->status(), 'body' => $response->body()]);
        return null;
    }

    public function updateForUser(User $user, Meeting $meeting, string $eventId): bool
    {
        $token = $this->getAccessToken($user);
        if (!$token) return false;

        $calendarId = $user->google_calendar_id ?? 'primary';
        $url = str_replace('{calendarId}', rawurlencode($calendarId), self::EVENTS_URL) . '/' . $eventId;

        $response = Http::withToken($token)->put($url, $this->buildBody($meeting));

        return $response->successful();
    }

    public function deleteForUser(User $user, string $eventId): bool
    {
        $token = $this->getAccessToken($user);
        if (!$token) return false;

        $calendarId = $user->google_calendar_id ?? 'primary';
        $url = str_replace('{calendarId}', rawurlencode($calendarId), self::EVENTS_URL) . '/' . $eventId;

        $response = Http::withToken($token)->delete($url);

        return $response->successful();
    }

    private function buildBody(Meeting $meeting): array
    {
        $tz = config('app.timezone', 'UTC');

        $body = [
            'summary'     => $meeting->title,
            'description' => $meeting->description,
            'start'       => ['dateTime' => $meeting->start_at->toIso8601String(), 'timeZone' => $tz],
            'end'         => ['dateTime' => $meeting->end_at->toIso8601String(),   'timeZone' => $tz],
            'attendees'   => $meeting->participants
                ->filter(fn ($p) => !empty($p->participant_email))
                ->map(fn ($p) => ['email' => $p->participant_email])
                ->values()
                ->toArray(),
        ];

        if ($meeting->is_online) {
            $body['conferenceData'] = [
                'createRequest' => [
                    'requestId'            => 'meeting-' . $meeting->id . '-' . time(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        return $body;
    }
}
