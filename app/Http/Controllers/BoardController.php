<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardPermission;
use App\Models\BoardUserRead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardController extends Controller
{
    public function index(): View
    {
        $user   = auth()->user();
        $boards = Board::with(['permissions', 'cards.notes', 'cards.tasks', 'userReads'])
            ->get()
            ->filter(fn ($b) => $b->canRead($user))
            ->values();

        return view('boards.index', compact('boards', 'user'));
    }

    public function create(): View
    {
        $this->authorize('create', Board::class);
        return view('boards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Board::class);

        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'permissions' => 'nullable|array',
            'permissions.*.role'      => 'required|string',
            'permissions.*.can_read'  => 'nullable|boolean',
            'permissions.*.can_write' => 'nullable|boolean',
        ]);

        $board = Board::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        $this->syncPermissions($board, $request->input('permissions', []));

        return redirect()->route('boards.show', $board)->with('success', 'Board oluşturuldu.');
    }

    public function show(Board $board): View
    {
        $user = auth()->user();
        abort_unless($board->canRead($user), 403);

        $board->load(['permissions', 'cards.notes.author', 'cards.tasks.assignedTo', 'cards.permissions', 'creator', 'userReads']);

        // Mark board as read
        BoardUserRead::updateOrCreate(
            ['user_id' => $user->id, 'board_id' => $board->id],
            ['last_read_at' => now()]
        );

        $allUsers = User::orderBy('name')->get();

        return view('boards.show', compact('board', 'user', 'allUsers'));
    }

    public function edit(Board $board): View
    {
        $this->authorize('update', $board);
        $board->load('permissions');
        return view('boards.edit', compact('board'));
    }

    public function update(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('update', $board);

        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'permissions' => 'nullable|array',
        ]);

        $board->update([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncPermissions($board, $request->input('permissions', []));

        return redirect()->route('boards.show', $board)->with('success', 'Board güncellendi.');
    }

    public function destroy(Board $board): RedirectResponse
    {
        $this->authorize('delete', $board);
        $board->delete();
        return redirect()->route('boards.index')->with('success', 'Board silindi.');
    }

    private function syncPermissions(Board $board, array $permissions): void
    {
        $board->permissions()->delete();

        $roles = ['member', 'service_provider_user', 'agent_user', 'client'];

        foreach ($roles as $role) {
            $canRead  = !empty($permissions[$role]['can_read']);
            $canWrite = !empty($permissions[$role]['can_write']);

            if ($canRead || $canWrite) {
                BoardPermission::create([
                    'board_id'  => $board->id,
                    'role'      => $role,
                    'can_read'  => $canRead,
                    'can_write' => $canWrite || $canRead ? $canWrite : false,
                ]);
            }
        }
    }
}
