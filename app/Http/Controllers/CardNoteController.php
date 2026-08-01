<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\CardNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardNoteController extends Controller
{
    public function store(Request $request, Board $board, BoardCard $card): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($card->canWrite(auth()->user()), 403);

        $request->validate(['content' => 'required|string|max:5000']);

        $card->notes()->create([
            'content'    => $request->input('content'),
            'created_by' => auth()->id(),
        ]);

        return back()->with('note_success', 'Note added.')->with('opened_card', $card->id);
    }

    public function destroy(Board $board, BoardCard $card, CardNote $note): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($note->card_id === $card->id, 404);

        $user = auth()->user();
        abort_unless(
            $user->isInternalAdmin() || ($note->created_by === $user->id && $note->created_at->diffInHours(now()) < 24),
            403
        );

        $note->delete();

        return back()->with('note_success', 'Note deleted.')->with('opened_card', $card->id);
    }
}
