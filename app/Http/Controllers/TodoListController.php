<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TodoListController extends Controller
{
    public function index(): View
    {
        $user = auth()->user()->loadMissing('company');

        $lists = TodoList::with(['members.user', 'items', 'boards'])
            ->get()
            ->filter(fn ($l) => $l->canRead($user))
            ->values();

        return view('todo-lists.index', compact('lists', 'user'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        $allUsers = $this->selectableUsers();
        $allBoards = Board::orderBy('title')->get();
        return view('todo-lists.create', compact('allUsers', 'allBoards'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
            'boards'      => 'nullable|array',
            'boards.*'    => 'exists:boards,id',
        ]);

        $list = TodoList::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        $this->syncMembers($list, $request->input('members', []));
        $list->boards()->sync($request->input('boards', []));

        return redirect()->route('todo-lists.show', $list)->with('success', 'ToDo list created.');
    }

    public function show(TodoList $todoList): View
    {
        $user = auth()->user()->loadMissing('company');
        abort_unless($todoList->canRead($user), 403);

        $todoList->load([
            'members.user',
            'items.creator',
            'items.completer',
            'boards',
            'creator',
        ]);

        $allUsers  = $this->selectableUsers();
        $allBoards = Board::orderBy('title')->get();

        return view('todo-lists.show', compact('todoList', 'user', 'allUsers', 'allBoards'));
    }

    public function update(Request $request, TodoList $todoList): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $request->validate([
            'title'       => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'members'     => 'nullable|array',
            'members.*'   => 'exists:users,id',
            'boards'      => 'nullable|array',
            'boards.*'    => 'exists:boards,id',
        ]);

        $todoList->update([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        $this->syncMembers($todoList, $request->input('members', []));
        $todoList->boards()->sync($request->input('boards', []));

        return back()->with('success', 'List updated.');
    }

    public function destroy(TodoList $todoList): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        $todoList->delete();
        return redirect()->route('todo-lists.index')->with('success', 'List deleted.');
    }

    private function syncMembers(TodoList $list, array $memberIds): void
    {
        \DB::table('todo_list_members')->where('todo_list_id', $list->id)->delete();
        foreach ($memberIds as $userId) {
            \DB::table('todo_list_members')->insert([
                'todo_list_id' => $list->id,
                'user_id'      => $userId,
            ]);
        }
    }

    private function selectableUsers()
    {
        return User::with('company')->orderBy('name')->get();
    }
}
