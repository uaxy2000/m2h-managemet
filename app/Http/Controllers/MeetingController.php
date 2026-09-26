<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(private GoogleCalendarService $gcal) {}

    public function index(Request $request)
    {
        $user            = auth()->user();
        $isInternalAdmin = $user->isInternalAdmin();
        $viewMode        = $request->get('view', 'week');
        $weekOffset      = (int) $request->get('week_offset', 0);
        $monthOffset     = (int) $request->get('month_offset', 0);

        $query = Meeting::with(['room', 'participants', 'creator']);

        if (!$isInternalAdmin) {
            $query->whereHas('participants', function ($q) use ($user) {
                $q->where('participant_type', 'internal_user')->where('participant_id', $user->id);
            });
        }

        $meetings = $query->get();

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $weekDays = collect(range(0, 6))->map(fn ($i) => [
            'date'     => $weekStart->copy()->addDays($i),
            'meetings' => $meetings->filter(fn ($m) => $m->start_at->isSameDay($weekStart->copy()->addDays($i)))
                ->sortBy('start_at')->values(),
        ]);

        $monthStart = now()->startOfMonth()->addMonths($monthOffset);
        $monthEnd   = $monthStart->copy()->endOfMonth();
        $calStart   = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calEnd     = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $monthGridDays = collect();
        $cur = $calStart->copy();
        while ($cur->lte($calEnd)) {
            $day = $cur->copy();
            $monthGridDays->push([
                'date'     => $day,
                'inMonth'  => $day->month === $monthStart->month,
                'meetings' => $meetings->filter(fn ($m) => $m->start_at->isSameDay($day))->sortBy('start_at')->values(),
            ]);
            $cur->addDay();
        }
        $monthGrid = $monthGridDays->chunk(7);

        $rooms       = MeetingRoom::where('is_active', true)->orderBy('name')->get();
        $filterUsers = $isInternalAdmin ? User::whereHas('company', fn ($q) => $q->where('type', 'internal'))->orderBy('name')->get(['id', 'name']) : collect();

        return view('meetings.index', compact(
            'meetings', 'weekDays', 'monthGrid', 'monthStart', 'monthEnd',
            'weekStart', 'weekEnd', 'viewMode', 'weekOffset', 'monthOffset',
            'rooms', 'filterUsers', 'isInternalAdmin'
        ));
    }

    public function create()
    {
        $rooms         = MeetingRoom::where('is_active', true)->orderBy('name')->get();
        $internalUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->orderBy('name')->get(['id', 'name', 'email']);

        return view('meetings.create', compact('rooms', 'internalUsers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'room_id'     => 'nullable|exists:meeting_rooms,id',
            'is_online'   => 'boolean',
            'participants' => 'nullable|array',
        ]);

        // Room conflict check
        if (!empty($data['room_id'])) {
            $conflict = Meeting::where('room_id', $data['room_id'])
                ->where('start_at', '<', $data['end_at'])
                ->where('end_at', '>', $data['start_at'])
                ->exists();

            if ($conflict) {
                return back()->withErrors(['room_id' => 'This room is already booked for the selected time.'])->withInput();
            }
        }

        $meeting = Meeting::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'start_at'    => $data['start_at'],
            'end_at'      => $data['end_at'],
            'room_id'     => $data['room_id'] ?? null,
            'is_online'   => $request->boolean('is_online'),
            'created_by'  => auth()->id(),
        ]);

        // Save participants
        $this->syncParticipants($meeting, $request->input('participants', []));

        // Push to Google Calendar for connected users
        $this->pushToGoogleCalendar($meeting);

        return redirect()->route('meetings.index')->with('success', 'Meeting created.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->load(['room', 'participants', 'creator']);
        return view('meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting)
    {
        $rooms         = MeetingRoom::where('is_active', true)->orderBy('name')->get();
        $internalUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->orderBy('name')->get(['id', 'name', 'email']);
        $meeting->load('participants');

        return view('meetings.edit', compact('meeting', 'rooms', 'internalUsers'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'room_id'     => 'nullable|exists:meeting_rooms,id',
            'is_online'   => 'boolean',
            'participants' => 'nullable|array',
        ]);

        if (!empty($data['room_id'])) {
            $conflict = Meeting::where('room_id', $data['room_id'])
                ->where('id', '!=', $meeting->id)
                ->where('start_at', '<', $data['end_at'])
                ->where('end_at', '>', $data['start_at'])
                ->exists();

            if ($conflict) {
                return back()->withErrors(['room_id' => 'This room is already booked for the selected time.'])->withInput();
            }
        }

        $meeting->update([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'start_at'    => $data['start_at'],
            'end_at'      => $data['end_at'],
            'room_id'     => $data['room_id'] ?? null,
            'is_online'   => $request->boolean('is_online'),
        ]);

        $this->syncParticipants($meeting, $request->input('participants', []));
        $this->updateGoogleCalendar($meeting);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting updated.');
    }

    public function destroy(Meeting $meeting)
    {
        $this->deleteFromGoogleCalendar($meeting);
        $meeting->delete();

        return redirect()->route('meetings.index')->with('success', 'Meeting deleted.');
    }

    // Participant conflict check (AJAX)
    public function checkConflicts(Request $request)
    {
        $startAt      = $request->input('start_at');
        $endAt        = $request->input('end_at');
        $participantIds = (array) $request->input('participant_ids', []);
        $excludeId    = $request->input('exclude_id');

        $conflicts = [];

        foreach ($participantIds as $pid) {
            $busy = MeetingParticipant::whereHas('meeting', function ($q) use ($startAt, $endAt, $excludeId) {
                $q->where('start_at', '<', $endAt)->where('end_at', '>', $startAt);
                if ($excludeId) {
                    $q->where('id', '!=', $excludeId);
                }
            })->where('participant_type', 'internal_user')->where('participant_id', $pid)->exists();

            if ($busy) {
                $conflicts[] = $pid;
            }
        }

        return response()->json(['conflicts' => $conflicts]);
    }

    public function participantSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $leads = Lead::where(function ($query) use ($q) {
            $query->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
        })->limit(8)->get()->map(fn ($l) => [
            'id'    => (string) $l->id,
            'type'  => 'lead',
            'name'  => $l->fullName(),
            'email' => $l->email,
        ]);

        $users = User::whereHas('company', fn ($c) => $c->whereIn('type', ['agent', 'service_provider']))
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            })->limit(5)->get()->map(fn ($u) => [
                'id'    => (string) $u->id,
                'type'  => $u->company?->type === 'agent' ? 'agent' : 'service_provider',
                'name'  => $u->name,
                'email' => $u->email,
            ]);

        return response()->json($leads->concat($users)->values());
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function syncParticipants(Meeting $meeting, array $participants): void
    {
        $meeting->participants()->delete();

        foreach ($participants as $p) {
            if (empty($p['type']) || empty($p['id'])) {
                continue;
            }

            MeetingParticipant::create([
                'meeting_id'       => $meeting->id,
                'participant_type' => $p['type'],
                'participant_id'   => $p['id'],
                'participant_name' => $p['name'] ?? '',
                'participant_email'=> $p['email'] ?? null,
            ]);
        }
    }

    private function pushToGoogleCalendar(Meeting $meeting): void
    {
        $meeting->load('participants');
        $internalIds = $meeting->participants
            ->where('participant_type', 'internal_user')
            ->pluck('participant_id');

        $users = User::whereIn('id', $internalIds)->whereNotNull('google_refresh_token')->get();

        foreach ($users as $user) {
            $eventId = $this->gcal->createForUser($user, $meeting);
            if ($eventId) {
                $meeting->participants()
                    ->where('participant_type', 'internal_user')
                    ->where('participant_id', $user->id)
                    ->update(['google_event_id' => $eventId]);

                // Store Meet URL from first connected user
                if ($meeting->is_online && !$meeting->online_url) {
                    // Will be set via the event's conference data after creation
                }
            }
        }
    }

    private function updateGoogleCalendar(Meeting $meeting): void
    {
        $meeting->load('participants');
        $participants = $meeting->participants->where('participant_type', 'internal_user');

        foreach ($participants as $participant) {
            if (!$participant->google_event_id) {
                continue;
            }
            $user = User::find($participant->participant_id);
            if ($user?->google_refresh_token) {
                $this->gcal->updateForUser($user, $meeting, $participant->google_event_id);
            }
        }
    }

    private function deleteFromGoogleCalendar(Meeting $meeting): void
    {
        $meeting->load('participants');
        foreach ($meeting->participants->where('participant_type', 'internal_user') as $p) {
            if (!$p->google_event_id) {
                continue;
            }
            $user = User::find($p->participant_id);
            if ($user?->google_refresh_token) {
                $this->gcal->deleteForUser($user, $p->google_event_id);
            }
        }
    }
}
