<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $pipelineId = $request->get('pipeline');
        $pipelines  = Pipeline::where('is_active', true)->orderBy('sort_order')->get();

        // Stage distribution
        $stagesQuery = Stage::withCount(['leads' => function ($q) use ($pipelineId) {
            if ($pipelineId) $q->where('pipeline_id', $pipelineId);
        }])->with('pipeline');

        if ($pipelineId) {
            $stagesQuery->where('pipeline_id', $pipelineId);
        }

        $stageDistribution = $stagesQuery->get()
            ->filter(fn ($s) => $s->leads_count > 0)
            ->sortByDesc('leads_count')
            ->values();

        // Source breakdown
        $baseLeads = Lead::query()->when($pipelineId, fn ($q) => $q->where('pipeline_id', $pipelineId));

        $sourceBreakdown = [
            ['label' => 'Meta Ad', 'count' => (clone $baseLeads)->where('source', 'meta_ad')->count(), 'color' => '#6366f1'],
            ['label' => 'Agent',   'count' => (clone $baseLeads)->whereNotNull('agent_id')->count(),    'color' => '#f97316'],
            ['label' => 'Manual',  'count' => (clone $baseLeads)->whereNull('source')->whereNull('agent_id')->count(), 'color' => '#64748b'],
        ];

        // Assignee breakdown
        $assigneeCounts = DB::table('leads')
            ->select('assigned_to', DB::raw('COUNT(*) as leads_count'))
            ->whereNotNull('assigned_to')
            ->when($pipelineId, fn ($q) => $q->where('pipeline_id', $pipelineId))
            ->groupBy('assigned_to')
            ->orderByDesc('leads_count')
            ->pluck('leads_count', 'assigned_to');

        $assigneeBreakdown = User::whereIn('id', $assigneeCounts->keys())
            ->get()
            ->map(fn ($u) => ['name' => $u->name, 'count' => $assigneeCounts[$u->id] ?? 0])
            ->sortByDesc('count')
            ->values();

        // Monthly trend (last 6 months)
        $monthlyTrend = Lead::query()
            ->when($pipelineId, fn ($q) => $q->where('pipeline_id', $pipelineId))
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill in any missing months
        $trendData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $trendData->push([
                'month' => now()->subMonths($i)->format('M Y'),
                'count' => $monthlyTrend->get($key)?->count ?? 0,
            ]);
        }

        // Summary totals
        $totalLeads    = (clone $baseLeads)->count();
        $thisMonthLeads = (clone $baseLeads)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $lastMonthLeads = (clone $baseLeads)->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();

        return view('reports.index', compact(
            'pipelines', 'pipelineId',
            'stageDistribution', 'sourceBreakdown', 'assigneeBreakdown',
            'trendData', 'totalLeads', 'thisMonthLeads', 'lastMonthLeads'
        ));
    }
}
