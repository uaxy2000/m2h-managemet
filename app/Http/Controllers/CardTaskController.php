<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\CardTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardTaskController extends Controller
{
    public function store(Request $request, Board $board, BoardCard $card): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($card->canWrite(auth()->user()), 403);

        $request->validate([
            'title'       => 'required|string|max:191',
            'assigned_to' => 'nullable|exists:users,id',
            'due_at'      => 'nullable|date',
        ]);

        $card->tasks()->create([
            'title'       => $request->input('title'),
            'assigned_to' => $request->input('assigned_to'),
            'due_at'      => $request->input('due_at'),
            'created_by'  => auth()->id(),
        ]);

        return back()->with('task_success', 'Task added.')->with('opened_card', $card->id);
    }

    public function toggle(Board $board, BoardCard $card, CardTask $task): JsonResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($task->card_id === $card->id, 404);
        abort_unless($card->canWrite(auth()->user()), 403);

        $task->update(['is_done' => !$task->is_done]);

        return response()->json(['is_done' => $task->is_done]);
    }

    public function destroy(Board $board, BoardCard $card, CardTask $task): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($task->card_id === $card->id, 404);
        abort_unless($card->canWrite(auth()->user()), 403);

        $task->delete();

        return back()->with('task_success', 'Task deleted.');
    }
}
