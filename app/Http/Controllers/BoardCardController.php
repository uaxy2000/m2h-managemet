<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\CardPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoardCardController extends Controller
{
    public function store(Request $request, Board $board): RedirectResponse
    {
        abort_unless($board->canWrite(auth()->user()), 403);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'body'  => 'nullable|string|max:5000',
        ]);

        $maxOrder = $board->cards()->max('sort_order') ?? 0;

        $board->cards()->create([
            'title'      => $data['title'],
            'body'       => $data['body'] ?? null,
            'sort_order' => $maxOrder + 1,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Card added.');
    }

    public function update(Request $request, Board $board, BoardCard $card): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($board->canWrite(auth()->user()), 403);

        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'body'        => 'nullable|string|max:5000',
            'permissions' => 'nullable|array',
        ]);

        $card->update([
            'title' => $data['title'],
            'body'  => $data['body'] ?? null,
        ]);

        // Sync card-level permission overrides
        $card->permissions()->delete();
        $roles = ['member', 'service_provider_user', 'agent_user', 'client'];
        foreach ($roles as $role) {
            $canRead  = !empty($data['permissions'][$role]['can_read']);
            $canWrite = !empty($data['permissions'][$role]['can_write']);
            if ($canRead || $canWrite) {
                CardPermission::create([
                    'card_id'   => $card->id,
                    'role'      => $role,
                    'can_read'  => $canRead,
                    'can_write' => $canWrite,
                ]);
            }
        }

        return back()->with('success', 'Card updated.');
    }

    public function destroy(Board $board, BoardCard $card): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        abort_unless($board->canWrite(auth()->user()), 403);

        $card->delete();

        return back()->with('success', 'Card deleted.');
    }
}
