<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['required', 'uuid', 'exists:users,id'],
            'due_at'      => ['required', 'date'],
        ]);

        Task::create([
            ...$validated,
            'lead_id'    => $lead->id,
            'created_by' => auth()->id(),
            'is_done'    => false,
            'created_at' => now(),
        ]);

        return back()->with('task_success', 'Task added.')->withFragment('timeline');
    }

    public function toggle(Lead $lead, Task $task): JsonResponse
    {
        $task->update(['is_done' => !$task->is_done]);

        return response()->json(['is_done' => $task->is_done]);
    }

    public function destroy(Lead $lead, Task $task): RedirectResponse
    {
        if ($task->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $task->delete();

        return back()->with('task_success', 'Task deleted.')->withFragment('timeline');
    }
}
