<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\TodoListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TodoListItemController extends Controller
{
    public function store(Request $request, TodoList $todoList): RedirectResponse
    {
        $user = auth()->user()->loadMissing('company');
        abort_unless($todoList->canRead($user), 403);

        $request->validate(['body' => 'required|string|max:5000']);

        $max = $todoList->items()->max('sort_order') ?? 0;

        $todoList->items()->create([
            'body'       => $request->input('body'),
            'sort_order' => $max + 1,
            'created_by' => $user->id,
        ]);

        return back();
    }

    public function update(Request $request, TodoList $todoList, TodoListItem $item): RedirectResponse
    {
        $user = auth()->user()->loadMissing('company');
        abort_unless($todoList->canRead($user), 403);
        abort_unless($item->todo_list_id === $todoList->id, 404);

        $request->validate(['body' => 'required|string|max:5000']);
        $item->update(['body' => $request->input('body')]);

        return back();
    }

    public function toggle(TodoList $todoList, TodoListItem $item): RedirectResponse
    {
        $user = auth()->user()->loadMissing('company');
        abort_unless($todoList->canRead($user), 403);
        abort_unless($item->todo_list_id === $todoList->id, 404);

        if ($item->is_done) {
            $item->update([
                'is_done'      => false,
                'completed_by' => null,
                'completed_at' => null,
            ]);
        } else {
            $item->update([
                'is_done'      => true,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);
        }

        return back();
    }

    public function destroy(TodoList $todoList, TodoListItem $item): RedirectResponse
    {
        $user = auth()->user()->loadMissing('company');
        abort_unless($todoList->canRead($user), 403);
        abort_unless($item->todo_list_id === $todoList->id, 404);

        $item->delete();
        return back();
    }
}
