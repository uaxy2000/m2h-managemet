@extends('layouts.app')

@section('title', 'Meta Ads Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meta Ads Report</h1>
            @if($lastSynced)
                <p class="text-sm text-gray-500 mt-0.5">
                    Last synced: {{ \Carbon\Carbon::parse($lastSynced)->diffForHumans() }}
                    @if($lastSyncDate)
                        · Data through: <strong>{{ \Carbon\Carbon::parse($lastSyncDate)->format('d M Y') }}</strong>
                    @endif
                    @if($missingDays > 0)
                        · <span class="text-amber-600 font-medium">{{ $missingDays }} day(s) missing</span>
                    @endif
                </p>
            @else
                <p class="text-sm text-gray-500 mt-0.5">No data synced yet.</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2" x-data="{ showBackfill: false }">
            {{-- Smart sync button --}}
            <form method="POST" action="{{ route('reports.meta-ads.sync') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    @if($missingDays > 0)
                        Sync {{ $missingDays }} Missing Day(s)
                    @else
                        Sync Now
                    @endif
                </button>
            </form>

            {{-- Backfill toggle --}}
            <button type="button" @click="showBackfill = !showBackfill"
                class="px-3 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Backfill…
            </button>

            {{-- Backfill form --}}
            <div x-show="showBackfill" x-cloak
                class="absolute right-4 mt-12 z-10 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-72">
                <p class="text-sm font-medium text-gray-700 mb-3">Sync from a specific date</p>
                <form method="POST" action="{{ route('reports.meta-ads.sync') }}" class="flex flex-col gap-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">From date</label>
                        <input type="date" name="from_date"
                            value="{{ now()->subDays(29)->toDateString() }}"
                            max="{{ now()->toDateString() }}"
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <p class="text-xs text-gray-400">Max 60 days per sync run. Click multiple times for longer ranges.</p>
                    <button type="submit"
                        class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                        Start Backfill
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(!$isConfigured)
        <div class="mb-6 px-4 py-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm font-medium text-amber-900">META_ADS_ACCESS_TOKEN is not configured.</p>
            <p class="text-sm text-amber-700 mt-1">Add <code class="bg-amber-100 px-1 rounded">META_ADS_ACCESS_TOKEN</code> to your <code class="bg-amber-100 px-1 rounded">.env</code> file (M2H Ads app System User token), then run <strong>Sync Now</strong>.</p>
        </div>
    @endif

    {{-- Date preset filter --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'last_7d' => 'Last 7 Days', 'last_30d' => 'Last 30 Days', 'this_month' => 'This Month'] as $key => $label)
            <a href="{{ route('reports.meta-ads', ['preset' => $key]) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium transition
                      {{ $preset === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
        <span class="text-xs text-gray-400 self-center ml-1">
            {{ \Carbon\Carbon::parse($from)->format('d M') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>
    </div>

    {{-- Summary cards --}}
    @if($hasData)
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        @php
            $cards = [
                ['label' => 'Total Spend',   'value' => '₺' . number_format($totalSpend, 2),   'color' => '#6366f1'],
                ['label' => 'Leads',          'value' => number_format($totalLeads),             'color' => '#22c55e'],
                ['label' => 'CPL',            'value' => $avgCpl > 0 ? '₺' . number_format($avgCpl, 2) : '—', 'color' => '#f59e0b'],
                ['label' => 'Impressions',    'value' => number_format($totalImpr),              'color' => '#0ea5e9'],
                ['label' => 'Clicks',         'value' => number_format($totalClicks),            'color' => '#8b5cf6'],
                ['label' => 'CTR',            'value' => $avgCtr . '%',                          'color' => '#ec4899'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-1">{{ $card['label'] }}</div>
                <div class="text-xl font-bold" style="color: {{ $card['color'] }}">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Daily trend sparkline --}}
    @if($trend->count() > 1)
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-8">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Daily Spend & Leads</h2>
        <div class="overflow-x-auto">
            <div style="min-width:600px">
                @php
                    $maxSpend = $trend->max('spend') ?: 1;
                    $maxLeads = $trend->max('leads_count') ?: 1;
                    $barW     = max(20, floor(560 / max($trend->count(), 1)));
                @endphp
                <div class="flex items-end gap-1" style="height:80px">
                    @foreach($trend as $day)
                        @php
                            $spendH = (int) round($day->spend / $maxSpend * 80);
                            $leadsH = (int) round($day->leads_count / $maxLeads * 80);
                        @endphp
                        <div class="flex-1 flex items-end gap-0.5 group relative" title="{{ $day->date->format('d M') }}: ₺{{ number_format($day->spend,2) }} / {{ $day->leads_count }} leads">
                            <div class="flex-1 rounded-t" style="height:{{ $spendH }}px;background:#6366f1;opacity:0.8"></div>
                            <div class="flex-1 rounded-t" style="height:{{ $leadsH }}px;background:#22c55e;opacity:0.8"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-1 mt-1">
                    @foreach($trend as $day)
                        <div class="flex-1 text-center text-xs text-gray-400 truncate">{{ $day->date->format('d/m') }}</div>
                    @endforeach
                </div>
                <div class="flex gap-4 mt-2 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#6366f1"></span> Spend</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#22c55e"></span> Leads</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Campaign table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-8" x-data="{ openCampaigns: {}, openAdsets: {} }">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Campaigns</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-4 py-2 font-medium">Campaign</th>
                        <th class="text-right px-4 py-2 font-medium">Spend</th>
                        <th class="text-right px-4 py-2 font-medium">Leads</th>
                        <th class="text-right px-4 py-2 font-medium">CPL</th>
                        <th class="text-right px-4 py-2 font-medium">Impr.</th>
                        <th class="text-right px-4 py-2 font-medium">Clicks</th>
                        <th class="text-right px-4 py-2 font-medium">CTR</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($campaigns as $campaign)
                        @php
                            $campaignAdsets = $adsets->get($campaign->entity_id, collect());
                        @endphp
                        {{-- Campaign row --}}
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                <div class="flex items-center gap-2">
                                    @if($campaignAdsets->isNotEmpty())
                                        <button type="button"
                                            @click="openCampaigns['{{ $campaign->entity_id }}'] = !openCampaigns['{{ $campaign->entity_id }}']"
                                            class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 flex-shrink-0">
                                            <svg class="w-3.5 h-3.5 transition-transform"
                                                :class="openCampaigns['{{ $campaign->entity_id }}'] ? 'rotate-90' : ''"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="w-5 flex-shrink-0"></span>
                                    @endif
                                    <span class="truncate max-w-xs" title="{{ $campaign->entity_name }}">{{ $campaign->entity_name ?? $campaign->entity_id }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">₺{{ number_format($campaign->spend, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($campaign->leads_count > 0)
                                    <span class="font-semibold text-green-700">{{ $campaign->leads_count }}</span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">
                                {{ $campaign->cpl > 0 ? '₺' . number_format($campaign->cpl, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ number_format($campaign->impressions) }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ number_format($campaign->clicks) }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $campaign->ctr }}%</td>
                            <td class="px-4 py-3 text-right">
                                @if($campaign->leads_count > 0)
                                    <a href="{{ route('leads.index', ['meta_campaign_id' => $campaign->entity_id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-full hover:bg-indigo-100 transition whitespace-nowrap"
                                       title="View CRM leads for this campaign">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                                        </svg>
                                        View Leads
                                    </a>
                                @endif
                            </td>
                        </tr>

                        {{-- Adset rows --}}
                        @if($campaignAdsets->isNotEmpty())
                            <tr x-show="openCampaigns['{{ $campaign->entity_id }}']" x-cloak>
                                <td colspan="8" class="p-0">
                                    <div class="bg-gray-50 border-t border-gray-100">
                                        <table class="w-full text-sm">
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($campaignAdsets as $adset)
                                                    @php
                                                        $adsetAds = $ads->get($adset->entity_id, collect());
                                                    @endphp
                                                    <tr class="hover:bg-gray-100/50 transition-colors">
                                                        <td class="pl-10 pr-4 py-2.5 text-gray-700">
                                                            <div class="flex items-center gap-2">
                                                                @if($adsetAds->isNotEmpty())
                                                                    <button type="button"
                                                                        @click="openAdsets['{{ $adset->entity_id }}'] = !openAdsets['{{ $adset->entity_id }}']"
                                                                        class="w-4 h-4 flex items-center justify-center text-gray-400 hover:text-gray-600 flex-shrink-0">
                                                                        <svg class="w-3 h-3 transition-transform"
                                                                            :class="openAdsets['{{ $adset->entity_id }}'] ? 'rotate-90' : ''"
                                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                                        </svg>
                                                                    </button>
                                                                @else
                                                                    <span class="w-4 flex-shrink-0"></span>
                                                                @endif
                                                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                                </svg>
                                                                <span class="truncate max-w-xs text-xs" title="{{ $adset->entity_name }}">{{ $adset->entity_name ?? $adset->entity_id }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-right text-xs text-gray-700">₺{{ number_format($adset->spend, 2) }}</td>
                                                        <td class="px-4 py-2.5 text-right text-xs">
                                                            @if($adset->leads_count > 0)
                                                                <span class="text-green-700 font-semibold">{{ $adset->leads_count }}</span>
                                                            @else
                                                                <span class="text-gray-400">0</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2.5 text-right text-xs text-gray-500">
                                                            {{ $adset->cpl > 0 ? '₺' . number_format($adset->cpl, 2) : '—' }}
                                                        </td>
                                                        <td class="px-4 py-2.5 text-right text-xs text-gray-500">{{ number_format($adset->impressions) }}</td>
                                                        <td class="px-4 py-2.5 text-right text-xs text-gray-500">{{ number_format($adset->clicks) }}</td>
                                                        <td class="px-4 py-2.5 text-right text-xs text-gray-500">{{ $adset->ctr }}%</td>
                                                        <td class="px-4 py-2.5 text-right">
                                                            @if($adset->leads_count > 0)
                                                                <a href="{{ route('leads.index', ['meta_adset_id' => $adset->entity_id]) }}"
                                                                   class="inline-flex items-center gap-1 px-2 py-0.5 text-xs text-indigo-600 bg-indigo-50 rounded-full hover:bg-indigo-100 transition whitespace-nowrap">
                                                                    View Leads
                                                                </a>
                                                            @endif
                                                        </td>
                                                    </tr>

                                                    {{-- Ad rows --}}
                                                    @if($adsetAds->isNotEmpty())
                                                        <tr x-show="openAdsets['{{ $adset->entity_id }}']" x-cloak>
                                                            <td colspan="8" class="p-0">
                                                                <div class="bg-white border-t border-gray-100">
                                                                    <table class="w-full text-xs">
                                                                        <tbody class="divide-y divide-gray-50">
                                                                            @foreach($adsetAds as $ad)
                                                                                <tr class="hover:bg-gray-50 transition-colors">
                                                                                    <td class="pl-20 pr-4 py-2 text-gray-600">
                                                                                        <div class="flex items-center gap-1.5">
                                                                                            <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                                                            </svg>
                                                                                            <span class="truncate max-w-xs" title="{{ $ad->entity_name }}">{{ $ad->entity_name ?? $ad->entity_id }}</span>
                                                                                        </div>
                                                                                    </td>
                                                                                    <td class="px-4 py-2 text-right text-gray-700">₺{{ number_format($ad->spend, 2) }}</td>
                                                                                    <td class="px-4 py-2 text-right">
                                                                                        @if($ad->leads_count > 0)
                                                                                            <span class="text-green-700 font-semibold">{{ $ad->leads_count }}</span>
                                                                                        @else
                                                                                            <span class="text-gray-400">0</span>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="px-4 py-2 text-right text-gray-500">
                                                                                        {{ $ad->cpl > 0 ? '₺' . number_format($ad->cpl, 2) : '—' }}
                                                                                    </td>
                                                                                    <td class="px-4 py-2 text-right text-gray-500">{{ number_format($ad->impressions) }}</td>
                                                                                    <td class="px-4 py-2 text-right text-gray-500">{{ number_format($ad->clicks) }}</td>
                                                                                    <td class="px-4 py-2 text-right text-gray-500">{{ $ad->ctr }}%</td>
                                                                                    <td class="px-4 py-2 text-right">
                                                                                        @if($ad->leads_count > 0)
                                                                                            <a href="{{ route('leads.index', ['meta_ad_id' => $ad->entity_id]) }}"
                                                                                               class="inline-flex items-center gap-1 px-2 py-0.5 text-xs text-indigo-600 bg-indigo-50 rounded-full hover:bg-indigo-100 transition whitespace-nowrap">
                                                                                                View Leads
                                                                                            </a>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">
                                No campaign data for this period. Run <strong>Sync Now</strong> to fetch from Meta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @else
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <p class="font-medium text-gray-500">No data yet.</p>
        <p class="text-sm mt-1">Configure your <code class="bg-gray-100 px-1 rounded">META_ADS_ACCESS_TOKEN</code> and click <strong>Sync Now</strong>.</p>
    </div>
    @endif

</div>
@endsection
