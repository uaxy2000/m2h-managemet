<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Note;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'content'    => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'string', function ($attr, $value, $fail) {
                $valid = ['internal', 'service_provider', 'agent', 'client'];
                $parts = array_filter(array_map('trim', explode(',', $value)));
                if (empty($parts)) { $fail('Visibility is required.'); return; }
                if (in_array('internal', $parts) && count($parts) > 1) { $fail('Internal cannot be combined with other options.'); return; }
                foreach ($parts as $part) {
                    if (!in_array($part, $valid)) { $fail("Invalid visibility: {$part}"); return; }
                }
            }],
        ]);

        Note::create([
            'lead_id'    => $lead->id,
            'created_by' => auth()->id(),
            'content'    => $validated['content'],
            'visibility' => $validated['visibility'],
            'created_at' => now(),
        ]);

        if ($lead->assigned_to && $lead->assigned_to !== auth()->id()) {
            NotificationService::send(
                userId: $lead->assigned_to,
                type:   'note_added',
                title:  'New note on your lead',
                body:   $lead->first_name . ' ' . $lead->last_name . ': ' . mb_strimwidth($validated['content'], 0, 80, '…'),
                url:    route('leads.show', $lead->id),
                meta:   ['lead_id' => $lead->id],
            );
        }

        return back()->with('note_success', 'Note added.')->withFragment('timeline');
    }

    public function destroy(Lead $lead, Note $note): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isInternalAdmin()) {
            if ($note->created_by !== $user->id) {
                abort(403);
            }
            if ($note->created_at->diffInHours(now()) >= 12) {
                return back()->with('note_error', 'Notes can only be deleted within 12 hours of creation.');
            }
        }

        $note->delete();

        return back()->with('note_success', 'Note deleted.')->withFragment('timeline');
    }
}
