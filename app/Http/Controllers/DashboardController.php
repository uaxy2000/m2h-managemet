<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Task;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $authUser = auth()->user();

        $leadQuery = Lead::query();
        if ($authUser->role === 'user') {
            $leadQuery->where('assigned_to', $authUser->id);
        }

        $totalLeads      = (clone $leadQuery)->count();
        $metaLeads       = (clone $leadQuery)->where('source', 'meta_ad')->count();
        $duplicates      = (clone $leadQuery)->where('is_duplicate_flag', true)->count();
        $newThisWeek     = (clone $leadQuery)->where('created_at', '>=', now()->startOfWeek())->count();

        $taskQuery = Task::where('is_done', false);
        if ($authUser->role === 'user') {
            $taskQuery->whereHas('lead', fn ($q) => $q->where('assigned_to', $authUser->id));
        }
        $openTasks = $taskQuery->count();

        $recentLeads = (clone $leadQuery)
            ->with(['stage', 'assignedTo', 'tags'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'totalLeads', 'metaLeads', 'duplicates', 'newThisWeek',
            'openTasks', 'recentLeads'
        ));
    }
}
