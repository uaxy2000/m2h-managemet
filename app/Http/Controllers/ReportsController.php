<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Carbon\Carbon;
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

    public function performance(): View
    {
        $authUser = auth()->user();
        abort_unless($authUser->isInternal(), 403);

        $isAdmin          = $authUser->isInternalAdmin();
        $REGISTERED_STAGE = 'a2663266-e3b6-42cd-b998-a479287c5256';

        $users = User::with('company')
            ->whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->when(!$isAdmin, fn ($q) => $q->where('id', $authUser->id))
            ->orderBy('name')
            ->get();

        $userIds = $users->pluck('id');

        // Total leads assigned per user
        $assignedCounts = DB::table('leads')
            ->whereIn('assigned_to', $userIds)
            ->select('assigned_to', DB::raw('COUNT(*) as total'))
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        // Leads that have ever reached LEAD REGISTERED (grouped by assigned_to)
        $registeredCounts = DB::table('lead_status_history')
            ->join('leads', 'leads.id', '=', 'lead_status_history.lead_id')
            ->where('lead_status_history.to_stage_id', $REGISTERED_STAGE)
            ->whereIn('leads.assigned_to', $userIds)
            ->select('leads.assigned_to', DB::raw('COUNT(DISTINCT leads.id) as cnt'))
            ->groupBy('leads.assigned_to')
            ->pluck('cnt', 'assigned_to');

        // Average days from lead creation → LEAD REGISTERED
        $avgDaysMap = DB::table('lead_status_history')
            ->join('leads', 'leads.id', '=', 'lead_status_history.lead_id')
            ->where('lead_status_history.to_stage_id', $REGISTERED_STAGE)
            ->whereIn('leads.assigned_to', $userIds)
            ->select(
                'leads.assigned_to',
                DB::raw('ROUND(AVG(TIMESTAMPDIFF(DAY, leads.created_at, lead_status_history.changed_at)), 1) as avg_days')
            )
            ->groupBy('leads.assigned_to')
            ->pluck('avg_days', 'assigned_to');

        // Stage distribution per user
        $stageDistRaw = DB::table('leads')
            ->whereIn('assigned_to', $userIds)
            ->select('assigned_to', 'stage_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('assigned_to', 'stage_id')
            ->get()
            ->groupBy('assigned_to');

        $stageIds = $stageDistRaw->flatten()->pluck('stage_id')->unique();
        $stageMap = Stage::with('pipeline')->whereIn('id', $stageIds)->get()->keyBy('id');

        // Monthly registrations — last 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        $monthlyRaw = DB::table('lead_status_history')
            ->join('leads', 'leads.id', '=', 'lead_status_history.lead_id')
            ->where('lead_status_history.to_stage_id', $REGISTERED_STAGE)
            ->where('lead_status_history.changed_at', '>=', now()->subMonths(5)->startOfMonth())
            ->whereIn('leads.assigned_to', $userIds)
            ->select(
                'leads.assigned_to',
                DB::raw("DATE_FORMAT(lead_status_history.changed_at, '%Y-%m') as month"),
                DB::raw('COUNT(DISTINCT leads.id) as cnt')
            )
            ->groupBy('leads.assigned_to', 'month')
            ->get()
            ->groupBy('assigned_to');

        $userStats = $users->map(function ($u) use (
            $assignedCounts, $registeredCounts, $avgDaysMap,
            $stageDistRaw, $stageMap, $monthlyRaw, $months
        ) {
            $total      = (int) ($assignedCounts[$u->id]   ?? 0);
            $registered = (int) ($registeredCounts[$u->id] ?? 0);
            $uMonthly   = ($monthlyRaw[$u->id] ?? collect())->keyBy('month');

            return [
                'user'       => $u,
                'total'      => $total,
                'registered' => $registered,
                'conversion' => $total > 0 ? round($registered / $total * 100, 1) : 0,
                'avg_days'   => $avgDaysMap[$u->id] ?? null,
                'stage_dist' => ($stageDistRaw[$u->id] ?? collect())
                    ->map(fn ($r) => ['stage' => $stageMap[$r->stage_id] ?? null, 'count' => (int) $r->cnt])
                    ->filter(fn ($r) => $r['stage'] !== null)
                    ->groupBy(fn ($r) => $r['stage']->pipeline_id)
                    ->map(fn ($stages) => [
                        'pipeline' => $stages->first()['stage']->pipeline,
                        'stages'   => $stages->sortBy(fn ($r) => $r['stage']->sort_order ?? 999)->values(),
                    ])
                    ->sortBy(fn ($g) => $g['pipeline']?->sort_order ?? 999)
                    ->values(),
                'monthly'    => $months->map(fn ($m) => [
                    'label' => Carbon::createFromFormat('Y-m', $m)->format('M y'),
                    'count' => (int) ($uMonthly[$m]?->cnt ?? 0),
                ]),
            ];
        })->values();

        // Kazanç özeti (member için — sadece kendi verisi; admin görmez bu bloğu)
        $earnings = null;
        if (!$isAdmin) {
            $totalReg = (int) DB::table('lead_status_history')
                ->join('leads', 'leads.id', '=', 'lead_status_history.lead_id')
                ->where('leads.assigned_to', $authUser->id)
                ->where('lead_status_history.to_stage_id', $REGISTERED_STAGE)
                ->distinct('lead_status_history.lead_id')
                ->count('lead_status_history.lead_id');

            $paid = (int) DB::table('payment_leads')
                ->join('payments', 'payments.id', '=', 'payment_leads.payment_id')
                ->where('payments.user_id', $authUser->id)
                ->count();

            $lastPayment = DB::table('payments')
                ->where('user_id', $authUser->id)
                ->orderByDesc('paid_at')
                ->orderByDesc('created_at')
                ->first();

            $unitPrice = $lastPayment ? round($lastPayment->amount / $lastPayment->lead_count, 2) : null;
            $unpaid    = max(0, $totalReg - $paid);

            $earnings = [
                'total'             => $totalReg,
                'paid'              => $paid,
                'unpaid'            => $unpaid,
                'unit_price'        => $unitPrice,
                'currency'          => $lastPayment?->currency,
                'estimated_pending' => ($unitPrice && $unpaid > 0) ? round($unitPrice * $unpaid, 2) : null,
            ];
        }

        return view('performance.index', compact('userStats', 'isAdmin', 'months', 'earnings'));
    }
}
