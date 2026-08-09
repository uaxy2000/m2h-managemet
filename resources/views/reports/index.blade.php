@extends('layouts.app')

@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')

@php
$maxStage    = $stageDistribution->max('leads_count') ?: 1;
$maxSource   = collect($sourceBreakdown)->max('count') ?: 1;
$maxAssignee = $assigneeBreakdown->max('count') ?: 1;
$maxTrend    = collect($trendData)->max('count') ?: 1;
$monthDiff   = $lastMonthLeads > 0
    ? round(($thisMonthLeads - $lastMonthLeads) / $lastMonthLeads * 100)
    : ($thisMonthLeads > 0 ? 100 : 0);

$periodLabels = [
    'all_time'      => 'All time',
    'last_7_days'   => 'Last 7 days',
    'last_30_days'  => 'Last 30 days',
    'last_3_months' => 'Last 3 months',
    'last_6_months' => 'Last 6 months',
    'this_year'     => 'This year',
    'custom'        => 'Custom',
];
@endphp

<div x-data="{ showBreakdown: false, showCustom: {{ $period === 'custom' ? 'true' : 'false' }} }">

{{-- Filter bar --}}
<form method="GET" action="{{ route('reports.index') }}" x-ref="filterForm" class="mb-6 space-y-3">
    <input type="hidden" name="period" id="periodInput" value="{{ $period }}">

    <div class="flex flex-wrap items-center gap-3">
        {{-- Pipeline select --}}
        <select name="pipeline" onchange="this.form.submit()"
                class="rounded-lg border-gray-200 text-sm py-1.5 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All pipelines</option>
            @foreach($pipelines as $pl)
            <option value="{{ $pl->id }}" @selected($pipelineId === $pl->id)>{{ $pl->name }}</option>
            @endforeach
        </select>

        <div class="hidden sm:block w-px h-5 bg-gray-200"></div>

        {{-- Period presets --}}
        <div class="flex flex-wrap gap-1.5">
            @foreach($periodLabels as $p => $label)
            <button type="button"
                    @click="document.getElementById('periodInput').value = '{{ $p }}';
                            showCustom = '{{ $p }}' === 'custom';
                            @if($p !== 'custom') $refs.filterForm.submit(); @endif"
                    class="px-3 py-1 rounded-full text-xs font-medium transition-colors
                           {{ $period === $p ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Custom date range --}}
    <div x-show="showCustom" x-cloak class="flex items-center gap-2">
        <input type="date" name="date_from"
               value="{{ $dateFrom?->format('Y-m-d') }}"
               class="rounded-lg border-gray-200 text-sm py-1.5 px-3 focus:ring-indigo-500 focus:border-indigo-500">
        <span class="text-xs text-gray-400">to</span>
        <input type="date" name="date_to"
               value="{{ $dateTo?->format('Y-m-d') }}"
               class="rounded-lg border-gray-200 text-sm py-1.5 px-3 focus:ring-indigo-500 focus:border-indigo-500">
        <button type="submit"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors">
            Apply
        </button>
    </div>
</form>

{{-- Active filter badge --}}
@if($period !== 'all_time')
<div class="mb-4 flex items-center gap-2">
    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-xs text-indigo-600 font-medium">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
        </svg>
        @if($period === 'custom' && ($dateFrom || $dateTo))
            {{ $dateFrom?->format('d M Y') ?? '—' }} – {{ $dateTo?->format('d M Y') ?? '—' }}
        @else
            {{ $periodLabels[$period] ?? $period }}
        @endif
    </span>
    <a href="{{ route('reports.index', array_filter(['pipeline' => $pipelineId])) }}"
       class="text-xs text-gray-400 hover:text-red-500 transition-colors">× Clear</a>
</div>
@endif

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            {{ $period !== 'all_time' ? 'Leads in Period' : 'Total Leads' }}
        </p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalLeads) }}</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ $pipelineId ? 'In selected pipeline' : 'Across all pipelines' }}</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">This Month</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($thisMonthLeads) }}</p>
        <p class="text-xs mt-1.5 {{ $monthDiff >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
            {{ $monthDiff >= 0 ? '+' : '' }}{{ $monthDiff }}% vs last month
        </p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Month</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($lastMonthLeads) }}</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ now()->subMonth()->format('F Y') }}</p>
    </div>

</div>

{{-- Monthly trend --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Monthly Trend (Last 6 Months)</h3>
    <div class="flex items-end gap-3 h-32">
        @foreach($trendData as $point)
        @php $pct = $maxTrend > 0 ? round($point['count'] / $maxTrend * 100) : 0; @endphp
        <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
            <span class="text-xs font-semibold text-gray-700">{{ $point['count'] ?: '' }}</span>
            <div class="w-full rounded-t-md transition-all {{ $point['count'] > 0 ? 'bg-indigo-500' : 'bg-gray-100' }}"
                 style="height: {{ max($pct, $point['count'] > 0 ? 4 : 2) }}%"></div>
            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $point['month'] }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- Stage distribution + Source breakdown --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Stage distribution --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Leads by Stage</h3>
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <span class="text-xs text-gray-400">Show users</span>
                <div class="relative">
                    <input type="checkbox" x-model="showBreakdown" class="sr-only peer">
                    <div class="w-7 h-4 bg-gray-200 rounded-full peer peer-checked:bg-indigo-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform peer-checked:translate-x-3"></div>
                </div>
            </label>
        </div>
        @if($stageDistribution->isEmpty())
        <p class="text-sm text-gray-400">No data.</p>
        @else
        <div class="space-y-3">
            @foreach($stageDistribution as $stage)
            @php $pct = round($stage->leads_count / $maxStage * 100); @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $stage->color }}"></span>
                        <span class="text-sm text-gray-700 truncate">{{ $stage->name }}</span>
                        @if(!$pipelineId)
                        <span class="text-xs text-gray-400 flex-shrink-0">· {{ $stage->pipeline->name }}</span>
                        @endif
                    </div>
                    <span class="text-sm font-semibold text-gray-800 ml-3 flex-shrink-0">{{ $stage->leads_count }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all" style="width: {{ $pct }}%; background-color: {{ $stage->color }}"></div>
                </div>
                @if(isset($stageUserRaw[$stage->id]) && $stageUserRaw[$stage->id]->count() > 0)
                <div x-show="showBreakdown" x-cloak class="mt-2 space-y-1">
                    @foreach($stageUserRaw[$stage->id]->sortByDesc('cnt') as $row)
                    @php $upct = $stage->leads_count > 0 ? round($row->cnt / $stage->leads_count * 100) : 0; @endphp
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 w-20 truncate flex-shrink-0">{{ $stageUserNames[$row->assigned_to] ?? '—' }}</span>
                        <div class="flex-1 rounded-full h-1 min-w-0" style="background-color: #f1f5f9">
                            <div class="h-1 rounded-full transition-all" style="width: {{ max($upct, $row->cnt > 0 ? 4 : 0) }}%; background-color: rgba(99,102,241,0.45)"></div>
                        </div>
                        <span class="text-xs tabular-nums text-gray-600 w-6 text-right flex-shrink-0">{{ $row->cnt }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Source breakdown --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Leads by Source</h3>
        <div class="space-y-3">
            @foreach($sourceBreakdown as $source)
            @php $pct = $source['count'] > 0 ? round($source['count'] / $maxSource * 100) : 0; @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-700">{{ $source['label'] }}</span>
                    <span class="text-sm font-semibold text-gray-800">{{ $source['count'] }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all" style="width: {{ $pct }}%; background-color: {{ $source['color'] }}"></div>
                </div>
            </div>
            @endforeach
        </div>

        @if($totalLeads > 0)
        <div class="mt-5 pt-4 border-t border-gray-100 flex gap-4">
            @foreach($sourceBreakdown as $source)
            @if($source['count'] > 0)
            <div class="text-center">
                <p class="text-lg font-bold text-gray-800">{{ round($source['count'] / $totalLeads * 100) }}%</p>
                <p class="text-xs text-gray-400">{{ $source['label'] }}</p>
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>

</div>

{{-- Assignee workload --}}
@if($assigneeBreakdown->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Leads by Assignee</h3>
    <div class="space-y-3">
        @foreach($assigneeBreakdown as $row)
        @php $pct = round($row['count'] / $maxAssignee * 100); @endphp
        <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($row['name'], 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-700 truncate">{{ $row['name'] }}</span>
                    <span class="text-sm font-semibold text-gray-800 ml-3 flex-shrink-0">{{ $row['count'] }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

</div>{{-- x-data --}}

@endsection
