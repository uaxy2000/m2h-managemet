<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MeetingRoom;
use Illuminate\Http\Request;

class MeetingRoomController extends Controller
{
    public function index()
    {
        $rooms = MeetingRoom::orderBy('name')->get();
        return view('settings.meeting-rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:150',
            'color'    => 'required|string|size:7',
        ]);

        MeetingRoom::create($data + ['is_active' => true]);

        return back()->with('success', 'Room created.');
    }

    public function update(Request $request, MeetingRoom $meetingRoom)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'capacity'  => 'nullable|integer|min:1',
            'location'  => 'nullable|string|max:150',
            'color'     => 'required|string|size:7',
            'is_active' => 'boolean',
        ]);

        $meetingRoom->update($data);

        return back()->with('success', 'Room updated.');
    }

    public function destroy(MeetingRoom $meetingRoom)
    {
        if ($meetingRoom->meetings()->exists()) {
            return back()->with('error', 'Cannot delete a room that has meetings scheduled.');
        }

        $meetingRoom->delete();

        return back()->with('success', 'Room deleted.');
    }
}
