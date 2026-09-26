<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;

class GoogleCalendarService
{
    private function clientForUser(User $user): ?Client
    {
        if (!$user->google_refresh_token) {
            return null;
        }

        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

        return $client;
    }

    public function buildEvent(Meeting $meeting): Event
    {
        $event = new Event();
        $event->setSummary($meeting->title);
        $event->setDescription($meeting->description ?? '');

        $start = new EventDateTime();
        $start->setDateTime($meeting->start_at->toRfc3339String());
        $start->setTimeZone(config('app.timezone'));
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($meeting->end_at->toRfc3339String());
        $end->setTimeZone(config('app.timezone'));
        $event->setEnd($end);

        // Attendees from participants
        $attendees = $meeting->participants->map(function ($p) {
            $a = new EventAttendee();
            $a->setEmail($p->participant_email);
            $a->setDisplayName($p->participant_name);
            return $a;
        })->filter(fn ($a) => $a->getEmail())->values()->all();

        if ($attendees) {
            $event->setAttendees($attendees);
        }

        // Google Meet for online meetings
        if ($meeting->is_online) {
            $conferenceKey = new ConferenceSolutionKey();
            $conferenceKey->setType('hangoutsMeet');
            $createRequest = new CreateConferenceRequest();
            $createRequest->setRequestId(uniqid('m2h_', true));
            $createRequest->setConferenceSolutionKey($conferenceKey);
            $conferenceData = new ConferenceData();
            $conferenceData->setCreateRequest($createRequest);
            $event->setConferenceData($conferenceData);
        }

        return $event;
    }

    public function createForUser(User $user, Meeting $meeting): ?string
    {
        $client = $this->clientForUser($user);
        if (!$client) {
            return null;
        }

        $service   = new Calendar($client);
        $calId     = $user->google_calendar_id ?? 'primary';
        $conferenceVersion = $meeting->is_online ? 1 : 0;

        $created = $service->events->insert(
            $calId,
            $this->buildEvent($meeting),
            ['conferenceDataVersion' => $conferenceVersion, 'sendUpdates' => 'all']
        );

        return $created->getId();
    }

    public function updateForUser(User $user, Meeting $meeting, string $googleEventId): void
    {
        $client = $this->clientForUser($user);
        if (!$client) {
            return;
        }

        $service = new Calendar($client);
        $calId   = $user->google_calendar_id ?? 'primary';
        $service->events->update($calId, $googleEventId, $this->buildEvent($meeting), ['sendUpdates' => 'all']);
    }

    public function deleteForUser(User $user, string $googleEventId): void
    {
        $client = $this->clientForUser($user);
        if (!$client) {
            return;
        }

        $service = new Calendar($client);
        $calId   = $user->google_calendar_id ?? 'primary';
        try {
            $service->events->delete($calId, $googleEventId, ['sendUpdates' => 'all']);
        } catch (\Exception) {
        }
    }

    public function getAuthUrl(): string
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        return $client->createAuthUrl();
    }

    public function exchangeCode(string $code): array
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');

        return $client->fetchAccessTokenWithAuthCode($code);
    }
}
