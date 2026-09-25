<?php

namespace App\Http\Controllers;

use App\Models\CardTask;
use App\Models\Lead;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user     = auth()->user()->loadMissing('company');
        $ownOnly  = $this->ownLeadsOnly($user);

        $leadQuery = Lead::query()
            ->when($ownOnly, fn ($q) => $q->where('assigned_to', $user->id));

        $totalLeads  = (clone $leadQuery)->count();
        $metaLeads   = (clone $leadQuery)->where('source', 'meta_ad')->count();
        $duplicates  = (clone $leadQuery)->where('is_duplicate_flag', true)->count();
        $newThisWeek = (clone $leadQuery)->where('created_at', '>=', now()->startOfWeek())->count();

        $taskBase = Task::where('is_done', false)
            ->when($ownOnly, fn ($q) => $q->where('assigned_to', $user->id));

        $openTasks   = (clone $taskBase)->count();
        $todayTasks  = (clone $taskBase)->whereDate('due_at', today())->count();
        $overdueTasks = (clone $taskBase)->whereDate('due_at', '<', today())->count();

        $recentLeads = (clone $leadQuery)
            ->with(['stage', 'assignedTo', 'tags'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // This week's tasks for the current user
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $userFilter = fn ($q) => $q->where(fn ($q2) => $q2->where('assigned_to', $user->id)->orWhere('created_by', $user->id));

        $weekLeadTasks = Task::with(['lead:id,first_name,last_name'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$weekStart, $weekEnd])
            ->where($userFilter)
            ->get()
            ->map(fn ($t) => [
                'title'       => $t->title,
                'is_done'     => (bool) $t->is_done,
                'due_at'      => $t->due_at,
                'context'     => $t->lead ? trim(($t->lead->first_name ?? '') . ' ' . ($t->lead->last_name ?? '')) : null,
                'context_url' => $t->lead_id ? route('leads.show', $t->lead_id) : null,
            ]);

        $weekBoardTasks = CardTask::with(['card:id,board_id,title', 'card.board:id,title'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$weekStart, $weekEnd])
            ->where($userFilter)
            ->get()
            ->map(fn ($t) => [
                'title'       => $t->title,
                'is_done'     => (bool) $t->is_done,
                'due_at'      => $t->due_at,
                'context'     => $t->card?->board?->title ? ($t->card->board->title . ' · ' . ($t->card->title ?? '')) : null,
                'context_url' => $t->card?->board_id ? route('boards.show', $t->card->board_id) : null,
            ]);

        $weekTasks  = $weekLeadTasks->concat($weekBoardTasks)->sortBy('due_at')->values();
        $weekDayMap = [];
        foreach ($weekTasks as $t) {
            $key = $t['due_at']->format('Y-m-d');
            $weekDayMap[$key][] = $t;
        }

        return view('dashboard', compact(
            'totalLeads', 'metaLeads', 'duplicates', 'newThisWeek',
            'openTasks', 'todayTasks', 'overdueTasks', 'recentLeads', 'ownOnly',
            'weekStart', 'weekEnd', 'weekTasks', 'weekDayMap'
        ));
    }

    private function ownLeadsOnly($user): bool
    {
        if ($user->isInternalAdmin()) {
            return false;
        }

        return $user->company?->type === 'internal';
    }
}
