<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Task;
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

        return view('dashboard', compact(
            'totalLeads', 'metaLeads', 'duplicates', 'newThisWeek',
            'openTasks', 'todayTasks', 'overdueTasks', 'recentLeads', 'ownOnly'
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
