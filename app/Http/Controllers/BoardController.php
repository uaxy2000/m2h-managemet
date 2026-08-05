<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\TodoList;
use App\Models\BoardUserRead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $user->loadMissing('company');

        $boards = Board::with(['members.user', 'cards.notes', 'cards.tasks', 'userReads'])
            ->get()
            ->filter(fn ($b) => $b->canRead($user))
            ->values();

        return view('boards.index', compact('boards', 'user'));
    }

    private function selectableUsers()
    {
        // Exclude only internal-company admins/super_admins (they have automatic access).
        // Everyone else — including internal members and all non-internal users — must be
        // added explicitly and therefore appear in the member picker.
        return User::where(function ($q) {
            // All users from non-internal companies
            $q->whereHas('company', fn ($q2) => $q2->where('type', '!=', 'internal'));
        })->orWhere(function ($q) {
            // Internal company users who are NOT admin/super_admin
            $q->whereHas('company', fn ($q2) => $q2->where('type', 'internal'))
              ->whereNotIn('role', ['super_admin', 'admin']);
        })
        ->with('company')
        ->orderBy('name')
        ->get();
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        $allUsers = $this->selectableUsers();
        return view('boards.create', compact('allUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
        ]);

        $board = Board::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        $this->syncMembers($board, $request->input('members', []), $request->input('can_write', []));

        return redirect()->route('boards.show', $board)->with('success', 'Board created.');
    }

    public function show(Board $board): View
    {
        $user = auth()->user();
        abort_unless($board->canRead($user), 403);

        $board->load([
            'members.user',
            'cards.notes.author',
            'cards.tasks.assignedTo',
            'cards.permissions',
            'creator',
            'userReads',
            'todoLists.items.creator',
            'todoLists.items.completer',
            'todoLists.members',
        ]);

        $allUsers = $this->selectableUsers();

        // Users who can be assigned tasks: internal admins + explicit board members
        $memberUserIds = $board->members->pluck('user_id')->all();
        $assignableUsers = User::where(function ($q) {
            $q->whereHas('company', fn ($q2) => $q2->where('type', 'internal'))
              ->whereIn('role', ['super_admin', 'admin']);
        })->orWhereIn('id', $memberUserIds)
          ->orderBy('name')
          ->get();

        $todoLists = $board->todoLists->filter(fn ($l) => $l->canRead($user))->values();

        return view('boards.show', compact('board', 'user', 'allUsers', 'assignableUsers', 'todoLists'));
    }

    public function edit(Board $board): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        $board->load('members.user');
        $allUsers = $this->selectableUsers();
        return view('boards.edit', compact('board', 'allUsers'));
    }

    public function update(Request $request, Board $board): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
        ]);

        $board->update([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        $this->syncMembers($board, $request->input('members', []), $request->input('can_write', []));

        return redirect()->route('boards.show', $board)->with('success', 'Board updated.');
    }

    public function destroy(Board $board): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        $board->delete();
        return redirect()->route('boards.index')->with('success', 'Board deleted.');
    }

    public function markRead(Board $board): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        abort_unless($board->canRead($user), 403);

        \DB::table('board_user_reads')->upsert(
            [['user_id' => $user->id, 'board_id' => $board->id, 'last_read_at' => now()]],
            ['user_id', 'board_id'],
            ['last_read_at']
        );

        return response()->json(['ok' => true]);
    }

    private function syncMembers(Board $board, array $memberIds, array $canWriteIds): void
    {
        \DB::table('board_members')->where('board_id', $board->id)->delete();

        foreach ($memberIds as $userId) {
            \DB::table('board_members')->insert([
                'board_id'  => $board->id,
                'user_id'   => $userId,
                'can_write' => in_array($userId, $canWriteIds),
            ]);
        }
    }
}
