<?php

namespace App\Http\Controllers;

use App\Models\MetaInsight;
use App\Services\MetaAdsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class MetaAdsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        // Date range
        $preset = $request->get('preset', 'last_7d');
        [$from, $to] = $this->resolveRange($preset, $request);

        // Summary (account level)
        $summary = MetaInsight::where('entity_type', 'account')
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                SUM(spend)       as total_spend,
                SUM(impressions) as total_impressions,
                SUM(clicks)      as total_clicks,
                SUM(leads_count) as total_leads
            ')
            ->first();

        $totalSpend  = (float) ($summary->total_spend ?? 0);
        $totalLeads  = (int)   ($summary->total_leads ?? 0);
        $totalImpr   = (int)   ($summary->total_impressions ?? 0);
        $totalClicks = (int)   ($summary->total_clicks ?? 0);
        $avgCpl      = $totalLeads > 0 ? round($totalSpend / $totalLeads, 2) : 0;
        $avgCtr      = $totalImpr  > 0 ? round($totalClicks / $totalImpr * 100, 2) : 0;

        // Campaigns
        $campaigns = MetaInsight::where('entity_type', 'campaign')
            ->whereBetween('date', [$from, $to])
            ->groupBy('entity_id', 'entity_name')
            ->selectRaw('
                entity_id,
                entity_name,
                SUM(spend)       as spend,
                SUM(impressions) as impressions,
                SUM(clicks)      as clicks,
                SUM(leads_count) as leads_count
            ')
            ->orderByRaw('SUM(spend) DESC')
            ->get()
            ->map(function ($row) {
                $row->cpl = $row->leads_count > 0 ? round($row->spend / $row->leads_count, 2) : 0;
                $row->ctr = $row->impressions  > 0 ? round($row->clicks / $row->impressions * 100, 2) : 0;
                return $row;
            });

        // Adsets grouped by campaign
        $adsets = MetaInsight::where('entity_type', 'adset')
            ->whereBetween('date', [$from, $to])
            ->groupBy('entity_id', 'entity_name', 'parent_entity_id')
            ->selectRaw('
                entity_id,
                entity_name,
                parent_entity_id as campaign_id,
                SUM(spend)       as spend,
                SUM(impressions) as impressions,
                SUM(clicks)      as clicks,
                SUM(leads_count) as leads_count
            ')
            ->orderByRaw('SUM(spend) DESC')
            ->get()
            ->map(function ($row) {
                $row->cpl = $row->leads_count > 0 ? round($row->spend / $row->leads_count, 2) : 0;
                $row->ctr = $row->impressions  > 0 ? round($row->clicks / $row->impressions * 100, 2) : 0;
                return $row;
            })
            ->groupBy('campaign_id');

        // Ads grouped by adset
        $ads = MetaInsight::where('entity_type', 'ad')
            ->whereBetween('date', [$from, $to])
            ->groupBy('entity_id', 'entity_name', 'parent_entity_id')
            ->selectRaw('
                entity_id,
                entity_name,
                parent_entity_id as adset_id,
                SUM(spend)       as spend,
                SUM(impressions) as impressions,
                SUM(clicks)      as clicks,
                SUM(leads_count) as leads_count
            ')
            ->orderByRaw('SUM(spend) DESC')
            ->get()
            ->map(function ($row) {
                $row->cpl = $row->leads_count > 0 ? round($row->spend / $row->leads_count, 2) : 0;
                $row->ctr = $row->impressions  > 0 ? round($row->clicks / $row->impressions * 100, 2) : 0;
                return $row;
            })
            ->groupBy('adset_id');

        // Daily trend (for chart)
        $trend = MetaInsight::where('entity_type', 'account')
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->selectRaw('date, spend, leads_count')
            ->get();

        $lastSynced   = MetaInsight::max('synced_at');
        $lastSyncDate = MetaInsight::where('entity_type', 'account')->max('date');
        $missingDays  = $lastSyncDate
            ? max(0, Carbon::parse($lastSyncDate)->diffInDays(now()->toDateString()))
            : null;
        $hasData      = MetaInsight::exists();
        $isConfigured = app(MetaAdsService::class)->isConfigured();

        return view('reports.meta-ads', compact(
            'preset', 'from', 'to',
            'totalSpend', 'totalLeads', 'totalImpr', 'totalClicks', 'avgCpl', 'avgCtr',
            'campaigns', 'adsets', 'ads',
            'trend', 'lastSynced', 'lastSyncDate', 'missingDays', 'hasData', 'isConfigured'
        ));
    }

    public function sync(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        // Quick sync: just today + yesterday (used for the simple "Sync Now" button)
        Artisan::call('meta:sync-insights', ['--date' => now()->toDateString()]);
        Artisan::call('meta:sync-insights', ['--date' => now()->subDay()->toDateString()]);

        return back()->with('success', 'Synced today and yesterday.');
    }

    // Called via AJAX — syncs a single date, returns JSON
    public function syncDay(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $date = $request->input('date');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['error' => 'Invalid date'], 422);
        }

        try {
            Artisan::call('meta:sync-insights', ['--date' => $date]);
            $output = trim(Artisan::output());
            return response()->json(['ok' => true, 'date' => $date, 'message' => $output]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function missingDays(): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $lastSyncDate = MetaInsight::where('entity_type', 'account')->max('date');

        if (!$lastSyncDate) {
            // No data at all — return last 30 days
            $start = now()->subDays(29);
        } else {
            $start = Carbon::parse($lastSyncDate)->addDay();
        }

        $days = [];
        $current = $start->copy();
        $end     = now();

        while ($current->lte($end)) {
            $days[] = $current->toDateString();
            $current->addDay();
        }

        return response()->json(['days' => $days]);
    }

    private function resolveRange(string $preset, Request $request): array
    {
        $today = now()->toDateString();

        return match ($preset) {
            'today'      => [$today, $today],
            'yesterday'  => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
            'last_7d'    => [now()->subDays(6)->toDateString(), $today],
            'last_30d'   => [now()->subDays(29)->toDateString(), $today],
            'this_month' => [now()->startOfMonth()->toDateString(), $today],
            'custom'     => [
                $request->get('date_from', now()->subDays(6)->toDateString()),
                $request->get('date_to', $today),
            ],
            default      => [now()->subDays(6)->toDateString(), $today],
        };
    }
}
