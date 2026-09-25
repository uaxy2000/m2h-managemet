<?php

namespace App\Http\Controllers;

use App\Models\CardTask;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnifiedTaskController extends Controller
{
    public function index(Request $request): View
    {
        $user            = auth()->user();
        $isInternalAdmin = $user->isInternalAdmin();

        $viewMode    = $request->get('view', 'week');
        $weekOffset  = (int) $request->get('week_offset', 0);
        $monthOffset = (int) $request->get('month_offset', 0);

        $filterAssignedBy = array_values(array_filter((array) $request->input('assigned_by', [])));
        $filterAssignedTo = array_values(array_filter((array) $request->input('assigned_to', [])));
        $hasFilters       = !empty($filterAssignedBy) || !empty($filterAssignedTo);

        $applyFilter = function ($q) use ($user, $isInternalAdmin, $filterAssignedBy, $filterAssignedTo, $hasFilters) {
            if ($isInternalAdmin && $hasFilters) {
                if (!empty($filterAssignedBy)) {
                    $q->whereIn('created_by', $filterAssignedBy);
                }
                if (!empty($filterAssignedTo)) {
                    $q->whereIn('assigned_to', $filterAssignedTo);
                }
            } else {
                $q->where(fn ($q2) => $q2->where('assigned_to', $user->id)->orWhere('created_by', $user->id));
            }
        };

        // Lead tasks with due date
        $leadTasks = Task::with(['lead:id,first_name,last_name', 'assignedTo:id,name'])
            ->whereNotNull('due_at')
            ->where($applyFilter)
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

        // Board tasks with due date
        $boardTasks = CardTask::with(['card:id,board_id,title', 'card.board:id,title', 'assignedTo:id,name'])
            ->whereNotNull('due_at')
            ->where($applyFilter)
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

        // No due date
        $leadTasksNoDue = Task::with(['lead:id,first_name,last_name', 'assignedTo:id,name'])
            ->whereNull('due_at')->where('is_done', false)->where($applyFilter)->get()
            ->map(fn ($t) => [
                'id'          => $t->id, 'type' => 'lead', 'title' => $t->title,
                'is_done'     => false, 'due_at' => null,
                'context'     => $t->lead ? trim(($t->lead->first_name ?? '') . ' ' . ($t->lead->last_name ?? '')) : '—',
                'context_url' => $t->lead_id ? route('leads.show', $t->lead_id) : '#',
                'toggle_url'  => $t->lead_id ? route('leads.tasks.toggle', [$t->lead_id, $t->id]) : null,
                'assigned'    => $t->assignedTo?->name,
            ]);

        $boardTasksNoDue = CardTask::with(['card:id,board_id,title', 'card.board:id,title', 'assignedTo:id,name'])
            ->whereNull('due_at')->where('is_done', false)->where($applyFilter)->get()
            ->map(fn ($t) => [
                'id'          => $t->id, 'type' => 'board', 'title' => $t->title,
                'is_done'     => false, 'due_at' => null,
                'context'     => ($t->card?->board?->title ?? '?') . ' · ' . ($t->card?->title ?? '?'),
                'context_url' => $t->card?->board_id ? route('boards.show', $t->card->board_id) : '#',
                'toggle_url'  => ($t->card && $t->card->board_id)
                    ? route('boards.cards.tasks.toggle', [$t->card->board_id, $t->card_id, $t->id])
                    : null,
                'assigned'    => $t->assignedTo?->name,
            ]);

        $noDue = $leadTasksNoDue->concat($boardTasksNoDue)->values();
        $all   = $leadTasks->concat($boardTasks)->sortBy('due_at')->values();

        $today = now()->startOfDay();

        // Week grid
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->addWeeks($weekOffset);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $weekDays = collect(range(0, 6))->map(fn ($i) => [
            'date'  => $weekStart->copy()->addDays($i),
            'tasks' => $all->filter(fn ($t) => $t['due_at']->isSameDay($weekStart->copy()->addDays($i)))
                          ->sortBy('is_done')->values(),
        ]);

        // Month grid
        $monthStart = now()->startOfMonth()->addMonths($monthOffset);
        $monthEnd   = $monthStart->copy()->endOfMonth();
        $calStart   = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calEnd     = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $monthGridDays = collect();
        $cur = $calStart->copy();
        while ($cur->lte($calEnd)) {
            $day = $cur->copy();
            $monthGridDays->push([
                'date'    => $day,
                'inMonth' => $day->month === $monthStart->month,
                'tasks'   => $all->filter(fn ($t) => $t['due_at']->isSameDay($day))->sortBy('is_done')->values(),
            ]);
            $cur->addDay();
        }
        $monthGrid = $monthGridDays->chunk(7);

        $periodEnd = $viewMode === 'month' ? $monthEnd : $weekEnd;

        $overdue  = $all->filter(fn ($t) => !$t['is_done'] && $t['due_at']->lt($today))->values();
        $upcoming = $all->filter(fn ($t) => !$t['is_done'] && $t['due_at']->gt($periodEnd))->values();
        $done     = $all->filter(fn ($t) => $t['is_done'])->sortByDesc('due_at')->take(20)->values();

        $filterUsers = $isInternalAdmin
            ? User::where(fn ($q) => $q->whereIn('role', ['super_admin', 'admin', 'member']))
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('tasks.index', compact(
            'overdue', 'weekDays', 'upcoming', 'noDue', 'done',
            'isInternalAdmin', 'weekStart', 'weekEnd',
            'viewMode', 'weekOffset', 'monthOffset',
            'monthStart', 'monthEnd', 'monthGrid',
            'filterUsers', 'filterAssignedBy', 'filterAssignedTo', 'hasFilters'
        ));
    }
}
