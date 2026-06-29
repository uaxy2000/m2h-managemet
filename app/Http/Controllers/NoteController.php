<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'content'    => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'in:internal,shared'],
        ]);

        Note::create([
            'lead_id'    => $lead->id,
            'created_by' => auth()->id(),
            'content'    => $validated['content'],
            'visibility' => $validated['visibility'],
            'created_at' => now(),
        ]);

        return back()->with('note_success', 'Note added.')->withFragment('notes');
    }

    public function destroy(Lead $lead, Note $note): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            if ($note->created_by !== $user->id) {
                abort(403);
            }
            if ($note->created_at->diffInHours(now()) >= 12) {
                return back()->with('note_error', 'Notes can only be deleted within 12 hours of creation.');
            }
        }

        $note->delete();

        return back()->with('note_success', 'Note deleted.')->withFragment('notes');
    }
}
