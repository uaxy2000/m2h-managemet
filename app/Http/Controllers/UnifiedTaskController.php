<?php

namespace App\Http\Controllers;

use App\Models\CardTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\View\View;

class UnifiedTaskController extends Controller
{
    public function index(): View
    {
        $user            = auth()->user();
        $isInternalAdmin = $user->isInternalAdmin();

        // Lead tasks
        $leadTasks = Task::with(['lead:id,first_name,last_name', 'assignedTo:id,name'])
            ->when(!$isInternalAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotNull('due_at')
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'type'        => 'lead',
                'title'       => $t->title,
                'is_done'     => $t->is_done,
                'due_at'      => $t->due_at,
                'context'     => $t->lead ? trim(($t->lead->first_name ?? '') . ' ' . ($t->lead->last_name ?? '')) : '—',
                'context_url' => $t->lead_id ? route('leads.show', $t->lead_id) : '#',
                'toggle_url'  => $t->lead_id ? route('leads.tasks.toggle', [$t->lead_id, $t->id]) : null,
                'assigned'    => $t->assignedTo?->name,
            ]);

        // Board tasks
        $boardTasks = CardTask::with(['card:id,board_id,title', 'card.board:id,title', 'assignedTo:id,name'])
            ->when(!$isInternalAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNotNull('due_at')
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'type'        => 'board',
                'title'       => $t->title,
                'is_done'     => $t->is_done,
                'due_at'      => $t->due_at,
                'context'     => ($t->card?->board?->title ?? '?') . ' · ' . ($t->card?->title ?? '?'),
                'context_url' => $t->card?->board_id ? route('boards.show', $t->card->board_id) : '#',
                'toggle_url'  => ($t->card && $t->card->board_id)
                    ? route('boards.cards.tasks.toggle', [$t->card->board_id, $t->card_id, $t->id])
                    : null,
                'assigned'    => $t->assignedTo?->name,
            ]);

        // Tasks without a due date (no_date bucket)
        $leadTasksNoDue = Task::with(['lead:id,first_name,last_name', 'assignedTo:id,name'])
            ->when(!$isInternalAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNull('due_at')
            ->where('is_done', false)
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'type'        => 'lead',
                'title'       => $t->title,
                'is_done'     => false,
                'due_at'      => null,
                'context'     => $t->lead ? trim(($t->lead->first_name ?? '') . ' ' . ($t->lead->last_name ?? '')) : '—',
                'context_url' => $t->lead_id ? route('leads.show', $t->lead_id) : '#',
                'toggle_url'  => $t->lead_id ? route('leads.tasks.toggle', [$t->lead_id, $t->id]) : null,
                'assigned'    => $t->assignedTo?->name,
            ]);

        $boardTasksNoDue = CardTask::with(['card:id,board_id,title', 'card.board:id,title', 'assignedTo:id,name'])
            ->when(!$isInternalAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereNull('due_at')
            ->where('is_done', false)
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'type'        => 'board',
                'title'       => $t->title,
                'is_done'     => false,
                'due_at'      => null,
                'context'     => ($t->card?->board?->title ?? '?') . ' · ' . ($t->card?->title ?? '?'),
                'context_url' => $t->card?->board_id ? route('boards.show', $t->card->board_id) : '#',
                'toggle_url'  => ($t->card && $t->card->board_id)
                    ? route('boards.cards.tasks.toggle', [$t->card->board_id, $t->card_id, $t->id])
                    : null,
                'assigned'    => $t->assignedTo?->name,
            ]);

        $noDue = $leadTasksNoDue->concat($boardTasksNoDue)->values();

        // All tasks with due dates, merged
        $all = $leadTasks->concat($boardTasks)->sortBy('due_at')->values();

        $today     = now()->startOfDay();
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $overdue   = $all->filter(fn ($t) => !$t['is_done'] && $t['due_at']->lt($today))->values();
        $upcoming  = $all->filter(fn ($t) => !$t['is_done'] && $t['due_at']->gt($weekEnd))->values();
        $done      = $all->filter(fn ($t) => $t['is_done'])->sortByDesc('due_at')->take(20)->values();

        // Build week grid: 7 days, each with their tasks (done + pending)
        $weekDays = collect(range(0, 6))->map(fn ($i) => [
            'date'   => $weekStart->copy()->addDays($i),
            'tasks'  => $all->filter(fn ($t) =>
                $t['due_at']->isSameDay($weekStart->copy()->addDays($i))
            )->sortBy('is_done')->values(),
        ]);

        return view('tasks.index', compact(
            'overdue', 'weekDays', 'upcoming', 'noDue', 'done',
            'isInternalAdmin', 'weekStart', 'weekEnd'
        ));
    }
}
