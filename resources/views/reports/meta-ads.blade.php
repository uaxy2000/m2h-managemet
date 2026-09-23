@extends('layouts.app')

@section('title', 'Meta Ads Report')

@section('content')
{{-- Ad Preview Modal --}}
<div x-data="adPreview('{{ route('reports.meta-ads.preview', '__ID__') }}')"
     @open-ad-preview.window="open($event.detail.adId, $event.detail.adName)"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
     @keydown.escape.window="show = false" @click.self="show = false">
    <div class="bg-white rounded-2xl w-full max-w-lg flex flex-col max-h-[90vh]"
         style="box-shadow: 0 0 0 1px rgba(0,0,0,0.08), 0 25px 60px rgba(0,0,0,0.45), 0 8px 20px rgba(0,0,0,0.25)">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <h3 class="font-semibold text-gray-900 text-sm" x-text="adName"></h3>
                <p class="text-xs text-gray-400 mt-0.5">Ad Preview</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Format switcher --}}
                <select x-model="format" @change="loadPreview()"
                    class="text-xs border border-gray-200 rounded-lg px-2 py-1 text-gray-600 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    <option value="MOBILE_FEED_STANDARD">Mobile Feed</option>
                    <option value="DESKTOP_FEED_STANDARD">Desktop Feed</option>
                    <option value="INSTAGRAM_STANDARD">Instagram Feed</option>
                    <option value="INSTAGRAM_STORY">Instagram Story</option>
                </select>
                <button @click="show = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-auto p-4 flex items-start justify-center">
            <div x-show="loading" class="py-12 text-gray-400 text-sm">Loading preview…</div>
            <div x-show="error" class="py-12 text-red-500 text-sm" x-text="error"></div>
            <div x-show="!loading && !error" x-html="previewHtml" class="w-full"></div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6"
         x-data="metaSync('{{ route('reports.meta-ads.sync-day') }}', '{{ route('reports.meta-ads.missing-days') }}', '{{ csrf_token() }}')"
         @keydown.escape.window="showBackfill = false">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">Meta Ads Report</h1>
            @if($lastSynced)
                <p class="text-sm text-gray-500 mt-0.5">
                    Last synced: {{ \Carbon\Carbon::parse($lastSynced)->diffForHumans() }}
                    @if($lastSyncDate)
                        · Data through: <strong>{{ \Carbon\Carbon::parse($lastSyncDate)->format('d M Y') }}</strong>
                    @endif
                    @if($missingDays > 0)
                        · <span class="text-amber-600 font-medium">{{ $missingDays }} day(s) not yet synced</span>
                    @endif
                </p>
            @else
                <p class="text-sm text-gray-500 mt-0.5">No data synced yet.</p>
            @endif
        </div>

        <div class="relative flex flex-wrap items-center gap-2">
            {{-- Sync Now (today + yesterday only, no timeout risk) --}}
            <form method="POST" action="{{ route('reports.meta-ads.sync') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync Today
                </button>
            </form>

            {{-- Backfill button --}}
            <button type="button" @click="showBackfill = !showBackfill"
                class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                :class="showBackfill ? 'bg-gray-100' : ''">
                @if($missingDays > 0)
                    <span class="text-amber-600">Backfill {{ $missingDays }} day(s)…</span>
                @else
                    Backfill…
                @endif
            </button>

            {{-- Backfill panel --}}
            <div x-show="showBackfill" x-cloak @click.outside="showBackfill = false"
                class="absolute top-full right-0 mt-2 z-20 bg-white border border-gray-200 rounded-xl shadow-xl p-5 w-80">

                <p class="text-sm font-semibold text-gray-800 mb-1">Backfill historical data</p>
                <p class="text-xs text-gray-500 mb-4">Fetches day-by-day to avoid timeouts. Each day = ~3 sec.</p>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">From date</label>
                    <input type="date" x-model="fromDate"
                        max="{{ now()->toDateString() }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                {{-- Progress --}}
                <div x-show="running" class="mb-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span x-text="progressLabel"></span>
                        <span x-text="doneCount + ' / ' + totalCount"></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full transition-all"
                            :style="'width:' + (totalCount > 0 ? Math.round(doneCount/totalCount*100) : 0) + '%'"></div>
                    </div>
                    <p x-show="currentDate" class="text-xs text-gray-400 mt-1" x-text="'Syncing ' + currentDate + '…'"></p>
                </div>

                <div x-show="doneMessage" class="mb-3 text-sm text-green-700 font-medium" x-text="doneMessage"></div>
                <div x-show="errorMessage" class="mb-3 text-sm text-red-600" x-text="errorMessage"></div>

                <div class="flex gap-2">
                    <button type="button" @click="startBackfill()"
                        :disabled="running"
                        class="flex-1 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!running">Start Backfill</span>
                        <span x-show="running">Running…</span>
                    </button>
                    <button type="button" @click="showBackfill = false; running = false"
                        class="px-3 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @once
    <script>
    function adPreview(urlTemplate) {
        return {
            show:        false,
            loading:     false,
            error:       '',
            previewHtml: '',
            adName:      '',
            adId:        '',
            format:      'MOBILE_FEED_STANDARD',

            open(adId, adName) {
                this.adId   = adId;
                this.adName = adName;
                this.show   = true;
                this.loadPreview();
            },

            async loadPreview() {
                this.loading     = true;
                this.error       = '';
                this.previewHtml = '';
                const url = urlTemplate.replace('__ID__', this.adId) + '?format=' + this.format;
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (!res.ok || data.error) {
                        this.error = data.error ?? 'Preview unavailable.';
                    } else {
                        this.previewHtml = data.html;
                    }
                } catch (e) {
                    this.error = 'Network error: ' + e.message;
                } finally {
                    this.loading = false;
                }
            }
        }
    }

    function metaSync(syncDayUrl, missingDaysUrl, csrfToken) {
        return {
            showBackfill: {{ $missingDays > 0 ? 'true' : 'false' }},
            fromDate:     '{{ now()->subDays(29)->toDateString() }}',
            running:      false,
            doneCount:    0,
            totalCount:   0,
            currentDate:  '',
            progressLabel:'',
            doneMessage:  '',
            errorMessage: '',

            async startBackfill() {
                this.doneMessage  = '';
                this.errorMessage = '';
                this.doneCount    = 0;

                // Build list of dates from fromDate to today
                const start = new Date(this.fromDate);
                const end   = new Date();
                const days  = [];
                for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                    days.push(d.toISOString().slice(0, 10));
                }

                if (days.length === 0) {
                    this.errorMessage = 'No dates to sync.';
                    return;
                }

                this.totalCount   = days.length;
                this.running      = true;
                this.progressLabel = 'Fetching from Meta API…';

                for (const date of days) {
                    this.currentDate = date;
                    try {
                        const res = await fetch(syncDayUrl, {
                            method:  'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept':       'application/json',
                            },
                            body: JSON.stringify({ date }),
                        });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            this.errorMessage = 'Error on ' + date + ': ' + (err.error ?? res.status);
                            this.running = false;
                            return;
                        }
                    } catch (e) {
                        this.errorMessage = 'Network error on ' + date + ': ' + e.message;
                        this.running = false;
                        return;
                    }
                    this.doneCount++;
                }

                this.running      = false;
                this.currentDate  = '';
                this.doneMessage  = '✓ Backfill complete — ' + this.doneCount + ' day(s) synced. Reload to see updated data.';
            }
        }
    }
    </script>
    @endonce

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
            <table class="w-full text-sm" style="min-width:1100px">
                <thead>
                    {{-- Group header row --}}
                    <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-semibold uppercase tracking-wide">
                        <th class="text-left px-4 py-2 text-gray-500" rowspan="2">Campaign</th>
                        <th class="text-right px-3 py-2 text-gray-500" rowspan="2">Spend</th>
                        <th class="text-right px-3 py-2 text-gray-500" rowspan="2">Leads</th>
                        <th class="text-right px-3 py-2 text-gray-500" rowspan="2">CPL</th>
                        <th class="text-right px-3 py-2 text-gray-500" rowspan="2">Impr.</th>
                        <th class="text-right px-3 py-2 text-gray-500" rowspan="2">Clicks</th>
                        <th class="text-right px-3 py-2 text-gray-500" rowspan="2">CTR</th>
                        <th colspan="3" class="text-center px-3 py-1.5 text-emerald-700 bg-emerald-50 border-l-2 border-emerald-200">Registered</th>
                        <th colspan="3" class="text-center px-3 py-1.5 text-blue-700 bg-blue-50 border-l-2 border-blue-200">Meeting 1</th>
                        <th colspan="3" class="text-center px-3 py-1.5 text-violet-700 bg-violet-50 border-l-2 border-violet-200">Won</th>
                        <th rowspan="2" class="px-3 py-2"></th>
                    </tr>
                    {{-- Sub-column header row --}}
                    <tr class="bg-gray-50 border-b border-gray-200 text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                        <th class="text-right px-3 py-1.5 border-l-2 border-emerald-200">#</th>
                        <th class="text-right px-3 py-1.5">Rate</th>
                        <th class="text-right px-3 py-1.5">CPLR</th>
                        <th class="text-right px-3 py-1.5 border-l-2 border-blue-200">#</th>
                        <th class="text-right px-3 py-1.5">Rate</th>
                        <th class="text-right px-3 py-1.5">CPM1</th>
                        <th class="text-right px-3 py-1.5 border-l-2 border-violet-200">#</th>
                        <th class="text-right px-3 py-1.5">Rate</th>
                        <th class="text-right px-3 py-1.5">CPW</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($campaigns as $campaign)
                        @php $campaignAdsets = $adsets->get($campaign->entity_id, collect()); @endphp

                        {{-- Campaign row --}}
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($campaignAdsets->isNotEmpty())
                                        <button type="button"
                                            @click="openCampaigns['{{ $campaign->entity_id }}'] = !openCampaigns['{{ $campaign->entity_id }}']"
                                            class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 flex-shrink-0">
                                            <svg class="w-3.5 h-3.5 transition-transform" :class="openCampaigns['{{ $campaign->entity_id }}'] ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="w-5 flex-shrink-0"></span>
                                    @endif
                                    <span class="font-medium text-gray-800 truncate max-w-[200px]" title="{{ $campaign->entity_name }}">{{ $campaign->entity_name ?? $campaign->entity_id }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right font-medium text-gray-900 whitespace-nowrap">₺{{ number_format($campaign->spend, 2) }}</td>
                            <td class="px-3 py-3 text-right"><span class="{{ $campaign->leads_count > 0 ? 'font-semibold text-green-700' : 'text-gray-400' }}">{{ $campaign->leads_count }}</span></td>
                            <td class="px-3 py-3 text-right text-gray-600 whitespace-nowrap">{{ $campaign->cpl > 0 ? '₺'.number_format($campaign->cpl,2) : '—' }}</td>
                            <td class="px-3 py-3 text-right text-gray-500">{{ number_format($campaign->impressions) }}</td>
                            <td class="px-3 py-3 text-right text-gray-500">{{ number_format($campaign->clicks) }}</td>
                            <td class="px-3 py-3 text-right text-gray-500">{{ $campaign->ctr }}%</td>
                            {{-- Registered --}}
                            <td class="px-3 py-3 text-right border-l-2 border-emerald-100"><span class="{{ $campaign->f->reg > 0 ? 'font-semibold text-emerald-700' : 'text-gray-300' }}">{{ $campaign->f->reg ?: '—' }}</span></td>
                            <td class="px-3 py-3 text-right text-xs text-gray-500 whitespace-nowrap">{{ $campaign->f->reg_pct > 0 ? $campaign->f->reg_pct.'%' : '—' }}</td>
                            <td class="px-3 py-3 text-right text-xs text-gray-600 whitespace-nowrap">{{ $campaign->f->reg_cost !== null ? '₺'.number_format($campaign->f->reg_cost,2) : '—' }}</td>
                            {{-- Meeting 1 --}}
                            <td class="px-3 py-3 text-right border-l-2 border-blue-100"><span class="{{ $campaign->f->mtg > 0 ? 'font-semibold text-blue-700' : 'text-gray-300' }}">{{ $campaign->f->mtg ?: '—' }}</span></td>
                            <td class="px-3 py-3 text-right text-xs text-gray-500 whitespace-nowrap">{{ $campaign->f->mtg_pct > 0 ? $campaign->f->mtg_pct.'%' : '—' }}</td>
                            <td class="px-3 py-3 text-right text-xs text-gray-600 whitespace-nowrap">{{ $campaign->f->mtg_cost !== null ? '₺'.number_format($campaign->f->mtg_cost,2) : '—' }}</td>
                            {{-- Won --}}
                            <td class="px-3 py-3 text-right border-l-2 border-violet-100"><span class="{{ $campaign->f->won > 0 ? 'font-semibold text-violet-700' : 'text-gray-300' }}">{{ $campaign->f->won ?: '—' }}</span></td>
                            <td class="px-3 py-3 text-right text-xs text-gray-500 whitespace-nowrap">{{ $campaign->f->won_pct > 0 ? $campaign->f->won_pct.'%' : '—' }}</td>
                            <td class="px-3 py-3 text-right text-xs text-gray-600 whitespace-nowrap">{{ $campaign->f->won_cost !== null ? '₺'.number_format($campaign->f->won_cost,2) : '—' }}</td>
                            <td class="px-3 py-3 text-right">
                                @if($campaign->leads_count > 0)
                                    <a href="{{ route('leads.index', ['meta_campaign_id' => $campaign->entity_id]) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-full hover:bg-indigo-100 transition whitespace-nowrap">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                                        View Leads
                                    </a>
                                @endif
                            </td>
                        </tr>

                        {{-- Adset rows (flat, same columns) --}}
                        @foreach($campaignAdsets as $adset)
                            @php $adsetAds = $ads->get($adset->entity_id, collect()); @endphp
                            <tr class="bg-gray-50/80 hover:bg-gray-100/60 transition-colors"
                                x-show="openCampaigns['{{ $campaign->entity_id }}']" x-cloak>
                                <td class="py-2.5 pr-4 pl-10">
                                    <div class="flex items-center gap-2">
                                        @if($adsetAds->isNotEmpty())
                                            <button type="button"
                                                @click.stop="openAdsets['{{ $adset->entity_id }}'] = !openAdsets['{{ $adset->entity_id }}']"
                                                class="w-4 h-4 flex items-center justify-center text-gray-400 hover:text-gray-600 flex-shrink-0">
                                                <svg class="w-3 h-3 transition-transform" :class="openAdsets['{{ $adset->entity_id }}'] ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                        @else
                                            <span class="w-4 flex-shrink-0"></span>
                                        @endif
                                        <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <span class="text-xs text-gray-700 truncate max-w-[180px]" title="{{ $adset->entity_name }}">{{ $adset->entity_name ?? $adset->entity_id }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-700 whitespace-nowrap">₺{{ number_format($adset->spend, 2) }}</td>
                                <td class="px-3 py-2.5 text-right text-xs"><span class="{{ $adset->leads_count > 0 ? 'font-semibold text-green-700' : 'text-gray-400' }}">{{ $adset->leads_count }}</span></td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ $adset->cpl > 0 ? '₺'.number_format($adset->cpl,2) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500">{{ number_format($adset->impressions) }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500">{{ number_format($adset->clicks) }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500">{{ $adset->ctr }}%</td>
                                <td class="px-3 py-2.5 text-right text-xs border-l-2 border-emerald-100"><span class="{{ $adset->f->reg > 0 ? 'font-medium text-emerald-600' : 'text-gray-300' }}">{{ $adset->f->reg ?: '—' }}</span></td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ $adset->f->reg_pct > 0 ? $adset->f->reg_pct.'%' : '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-600 whitespace-nowrap">{{ $adset->f->reg_cost !== null ? '₺'.number_format($adset->f->reg_cost,2) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs border-l-2 border-blue-100"><span class="{{ $adset->f->mtg > 0 ? 'font-medium text-blue-600' : 'text-gray-300' }}">{{ $adset->f->mtg ?: '—' }}</span></td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ $adset->f->mtg_pct > 0 ? $adset->f->mtg_pct.'%' : '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-600 whitespace-nowrap">{{ $adset->f->mtg_cost !== null ? '₺'.number_format($adset->f->mtg_cost,2) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs border-l-2 border-violet-100"><span class="{{ $adset->f->won > 0 ? 'font-medium text-violet-600' : 'text-gray-300' }}">{{ $adset->f->won ?: '—' }}</span></td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ $adset->f->won_pct > 0 ? $adset->f->won_pct.'%' : '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-600 whitespace-nowrap">{{ $adset->f->won_cost !== null ? '₺'.number_format($adset->f->won_cost,2) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right">
                                    @if($adset->leads_count > 0)
                                        <a href="{{ route('leads.index', ['meta_adset_id' => $adset->entity_id]) }}"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 text-xs text-indigo-600 bg-indigo-50 rounded-full hover:bg-indigo-100 transition whitespace-nowrap">
                                            View Leads
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            {{-- Ad rows (flat, same columns) --}}
                            @foreach($adsetAds as $ad)
                                <tr class="bg-white hover:bg-indigo-50/20 transition-colors"
                                    x-show="openCampaigns['{{ $campaign->entity_id }}'] && openAdsets['{{ $adset->entity_id }}']" x-cloak>
                                    <td class="py-2 pr-4 pl-20">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            <button type="button"
                                                @click="$dispatch('open-ad-preview', { adId: '{{ $ad->entity_id }}', adName: '{{ addslashes($ad->entity_name ?? $ad->entity_id) }}' })"
                                                class="text-xs truncate max-w-[160px] text-left text-indigo-600 hover:underline cursor-pointer"
                                                title="Click to preview">
                                                {{ $ad->entity_name ?? $ad->entity_id }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-700 whitespace-nowrap">₺{{ number_format($ad->spend, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-xs"><span class="{{ $ad->leads_count > 0 ? 'font-semibold text-green-700' : 'text-gray-400' }}">{{ $ad->leads_count ?: '0' }}</span></td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500 whitespace-nowrap">{{ $ad->cpl > 0 ? '₺'.number_format($ad->cpl,2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500">{{ number_format($ad->impressions) }}</td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500">{{ number_format($ad->clicks) }}</td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500">{{ $ad->ctr }}%</td>
                                    <td class="px-3 py-2 text-right text-xs border-l-2 border-emerald-100"><span class="{{ $ad->f->reg > 0 ? 'font-medium text-emerald-600' : 'text-gray-300' }}">{{ $ad->f->reg ?: '—' }}</span></td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500 whitespace-nowrap">{{ $ad->f->reg_pct > 0 ? $ad->f->reg_pct.'%' : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-600 whitespace-nowrap">{{ $ad->f->reg_cost !== null ? '₺'.number_format($ad->f->reg_cost,2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-xs border-l-2 border-blue-100"><span class="{{ $ad->f->mtg > 0 ? 'font-medium text-blue-600' : 'text-gray-300' }}">{{ $ad->f->mtg ?: '—' }}</span></td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500 whitespace-nowrap">{{ $ad->f->mtg_pct > 0 ? $ad->f->mtg_pct.'%' : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-600 whitespace-nowrap">{{ $ad->f->mtg_cost !== null ? '₺'.number_format($ad->f->mtg_cost,2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-xs border-l-2 border-violet-100"><span class="{{ $ad->f->won > 0 ? 'font-medium text-violet-600' : 'text-gray-300' }}">{{ $ad->f->won ?: '—' }}</span></td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-500 whitespace-nowrap">{{ $ad->f->won_pct > 0 ? $ad->f->won_pct.'%' : '—' }}</td>
                                    <td class="px-3 py-2 text-right text-xs text-gray-600 whitespace-nowrap">{{ $ad->f->won_cost !== null ? '₺'.number_format($ad->f->won_cost,2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        @if($ad->leads_count > 0)
                                            <a href="{{ route('leads.index', ['meta_ad_id' => $ad->entity_id]) }}"
                                               class="inline-flex items-center gap-1 px-2 py-0.5 text-xs text-indigo-600 bg-indigo-50 rounded-full hover:bg-indigo-100 transition whitespace-nowrap">
                                                View Leads
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach

                    @empty
                        <tr>
                            <td colspan="17" class="px-4 py-8 text-center text-gray-400 text-sm">
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
