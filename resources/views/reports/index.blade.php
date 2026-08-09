@extends('layouts.app')

@section('title', 'Reports')
@section('heading', 'Reports')

@section('content')

@php
$maxStage     = $stageDistribution->max('leads_count') ?: 1;
$maxSource    = collect($sourceBreakdown)->max('count') ?: 1;
$maxAssignee  = $assigneeBreakdown->max('count') ?: 1;
$maxTrend     = collect($trendData)->max('count') ?: 1;
$trend        = collect($trendData);
$monthDiff    = $lastMonthLeads > 0
    ? round(($thisMonthLeads - $lastMonthLeads) / $lastMonthLeads * 100)
    : ($thisMonthLeads > 0 ? 100 : 0);
@endphp

{{-- Pipeline filter --}}
<form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-3 mb-6">
    <select name="pipeline" onchange="this.form.submit()"
            class="rounded-lg border-gray-200 text-sm py-1.5 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">All pipelines</option>
        @foreach($pipelines as $pl)
        <option value="{{ $pl->id }}" @selected($pipelineId === $pl->id)>{{ $pl->name }}</option>
        @endforeach
    </select>
    @if($pipelineId)
    <a href="{{ route('reports.index') }}" class="text-xs text-gray-400 hover:text-red-500 transition-colors">× Clear</a>
    @endif
</form>

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Leads</p>
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
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Leads by Stage</h3>
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
                <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5">
                    @foreach($stageUserRaw[$stage->id]->sortByDesc('cnt') as $row)
                    <span class="text-xs text-gray-400">
                        {{ $stageUserNames[$row->assigned_to] ?? '—' }}:
                        <span class="font-medium text-gray-600">{{ $row->cnt }}</span>
                    </span>
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

@endsection
