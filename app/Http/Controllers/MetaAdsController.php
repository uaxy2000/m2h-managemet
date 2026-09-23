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

        // Adsets (flat — grouped after funnel attachment)
        $adsetsFlat = MetaInsight::where('entity_type', 'adset')
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
            });

        // Ads (flat — grouped after funnel attachment)
        $adsFlat = MetaInsight::where('entity_type', 'ad')
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
            });

        // Funnel stage IDs (LEAD REGISTERED / MEETING 1 / WON)
        $stageMap = \App\Models\Stage::whereIn('name', ['LEAD REGISTERED', 'MEETING 1', 'WON'])
            ->pluck('id', 'name');
        $regId  = $stageMap->get('LEAD REGISTERED');
        $mtg1Id = $stageMap->get('MEETING 1');
        $wonId  = $stageMap->get('WON');

        $funnelQuery = fn (string $col) => \Illuminate\Support\Facades\DB::table('leads')
            ->whereNotNull($col)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw("$col as grp,
                COUNT(*) as total,
                SUM(CASE WHEN stage_id = ? THEN 1 ELSE 0 END) as reg,
                SUM(CASE WHEN stage_id = ? THEN 1 ELSE 0 END) as mtg,
                SUM(CASE WHEN stage_id = ? THEN 1 ELSE 0 END) as won",
                [$regId, $mtg1Id, $wonId])
            ->groupBy($col)
            ->get()
            ->keyBy('grp');

        $campaignFunnel = $funnelQuery('meta_campaign_id');
        $adsetFunnel    = $funnelQuery('meta_adset_id');
        $adFunnel       = $funnelQuery('meta_ad_id');

        $attachFunnel = function ($row, $fi) {
            $f   = $fi->get($row->entity_id);
            $n   = (int)   ($f?->total ?? 0);
            $reg = (int)   ($f?->reg   ?? 0);
            $mtg = (int)   ($f?->mtg   ?? 0);
            $won = (int)   ($f?->won   ?? 0);
            $s   = (float) $row->spend;
            $row->f = (object) [
                'reg'      => $reg,
                'reg_pct'  => $n > 0 ? round($reg / $n * 100, 1) : 0,
                'reg_cost' => ($reg > 0 && $s > 0) ? round($s / $reg, 2) : null,
                'mtg'      => $mtg,
                'mtg_pct'  => $n > 0 ? round($mtg / $n * 100, 1) : 0,
                'mtg_cost' => ($mtg > 0 && $s > 0) ? round($s / $mtg, 2) : null,
                'won'      => $won,
                'won_pct'  => $n > 0 ? round($won / $n * 100, 1) : 0,
                'won_cost' => ($won > 0 && $s > 0) ? round($s / $won, 2) : null,
            ];
            return $row;
        };

        $campaigns = $campaigns->map(fn ($r) => $attachFunnel($r, $campaignFunnel));
        $adsets    = $adsetsFlat->map(fn ($r) => $attachFunnel($r, $adsetFunnel))->groupBy('campaign_id');
        $ads       = $adsFlat->map(fn ($r) => $attachFunnel($r, $adFunnel))->groupBy('adset_id');

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

    public function preview(Request $request, string $adId): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $format = $request->get('format', 'MOBILE_FEED_STANDARD');
        $token  = config('services.meta_ads.access_token');

        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->get("https://graph.facebook.com/v20.0/{$adId}/previews", [
                'ad_format'    => $format,
                'access_token' => $token,
            ]);

        if (!$response->ok()) {
            return response()->json(['error' => 'Preview unavailable'], 422);
        }

        $data = $response->json();
        $body = $data['data'][0]['body'] ?? null;

        if (!$body) {
            return response()->json(['error' => 'No preview returned'], 422);
        }

        return response()->json(['html' => $body, 'format' => $format]);
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
