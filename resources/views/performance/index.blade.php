@extends('layouts.app')

@section('title', 'Performance')
@section('heading', 'Performance')

@section('content')

@php $REGISTERED_STAGE = 'a2663266-e3b6-42cd-b998-a479287c5256'; @endphp

{{-- ── Earnings card (member only) ────────────────────────────────────── --}}
@if(!$isAdmin && $earnings)
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Registered</p>
        <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ $earnings['total'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Paid</p>
        <p class="text-3xl font-bold text-green-600 mt-1 tabular-nums">{{ $earnings['paid'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unpaid</p>
        <p class="text-3xl font-bold {{ $earnings['unpaid'] > 0 ? 'text-amber-500' : 'text-gray-300' }} mt-1 tabular-nums">{{ $earnings['unpaid'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-indigo-200 p-5 bg-indigo-50/40">
        <p class="text-xs font-medium text-indigo-500 uppercase tracking-wide">Est. Pending</p>
        @if($earnings['estimated_pending'])
        <p class="text-2xl font-bold text-indigo-600 mt-1 tabular-nums">
            {{ number_format($earnings['estimated_pending'], 0, ',', '.') }}
            <span class="text-sm font-normal">{{ $earnings['currency'] }}</span>
        </p>
        <p class="text-xs text-indigo-400 mt-0.5">{{ number_format($earnings['unit_price'], 2) }} {{ $earnings['currency'] }} × {{ $earnings['unpaid'] }} leads</p>
        @else
        <p class="text-2xl font-bold text-gray-300 mt-1">—</p>
        @endif
    </div>
</div>
@endif

{{-- ── Summary table ─────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
            {{ $isAdmin ? 'Team Summary' : 'My Summary' }}
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3 font-medium">User</th>
                    <th class="text-right px-4 py-3 font-medium">Assigned</th>
                    <th class="text-right px-4 py-3 font-medium">Registered</th>
                    <th class="text-right px-4 py-3 font-medium">Conv. Rate</th>
                    <th class="text-right px-5 py-3 font-medium">Avg. Days to Register</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($userStats as $stat)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($stat['user']->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ $stat['user']->name }}</p>
                                <p class="text-xs text-gray-400">{{ $stat['user']->roleLabel() }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-700">
                        {{ $stat['total'] }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="font-semibold text-indigo-600">{{ $stat['registered'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($stat['total'] > 0)
                        <div class="inline-flex items-center gap-2">
                            <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ min($stat['conversion'], 100) }}%"></div>
                            </div>
                            <span class="text-gray-700 tabular-nums w-10 text-right">{{ $stat['conversion'] }}%</span>
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if($stat['avg_days'] !== null)
                        <span class="text-gray-700 tabular-nums">{{ $stat['avg_days'] }} days</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ── Monthly Registrations ─────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">
        Lead Registered — Last 6 Months
    </h3>

    @if($isAdmin && $userStats->count() > 1)
    {{-- Admin: table with one column per user --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                    <th class="text-left py-2 font-medium w-20">Month</th>
                    @foreach($userStats as $stat)
                    <th class="text-right px-3 py-2 font-medium">{{ $stat['user']->name }}</th>
                    @endforeach
                    <th class="text-right px-3 py-2 font-medium text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($userStats[0]['monthly'] as $mi => $month)
                @php $rowTotal = $userStats->sum(fn($s) => $s['monthly'][$mi]['count']); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="py-2 text-gray-500 text-xs font-medium">{{ $month['label'] }}</td>
                    @foreach($userStats as $stat)
                    <td class="px-3 py-2 text-right tabular-nums {{ $stat['monthly'][$mi]['count'] > 0 ? 'font-semibold text-indigo-600' : 'text-gray-300' }}">
                        {{ $stat['monthly'][$mi]['count'] ?: '—' }}
                    </td>
                    @endforeach
                    <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-700">
                        {{ $rowTotal ?: '—' }}
                    </td>
                </tr>
                @endforeach
                {{-- Totals row --}}
                <tr class="border-t border-gray-200 bg-gray-50 font-semibold text-xs">
                    <td class="py-2 text-gray-500 uppercase tracking-wide">Total</td>
                    @foreach($userStats as $stat)
                    <td class="px-3 py-2 text-right tabular-nums text-indigo-600">
                        {{ $stat['monthly']->sum('count') }}
                    </td>
                    @endforeach
                    <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                        {{ $userStats->sum(fn($s) => $s['monthly']->sum('count')) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    {{-- Single user: bar chart --}}
    @php $stat = $userStats->first(); $maxMonthly = $stat ? max(1, $stat['monthly']->max('count')) : 1; @endphp
    @if($stat)
    <div class="flex items-end gap-3 h-28">
        @foreach($stat['monthly'] as $month)
        <div class="flex-1 flex flex-col items-center gap-1">
            <span class="text-xs text-gray-500 font-medium tabular-nums">{{ $month['count'] ?: '' }}</span>
            <div class="w-full rounded-t flex items-end" style="height:80px">
                <div class="w-full rounded-t bg-indigo-500 transition-all"
                     style="height:{{ $maxMonthly > 0 ? round($month['count'] / $maxMonthly * 80) : 0 }}px; min-height:{{ $month['count'] > 0 ? 4 : 0 }}px"></div>
            </div>
            <span class="text-xs text-gray-400">{{ $month['label'] }}</span>
        </div>
        @endforeach
    </div>
    @endif
    @endif
</div>

{{-- ── Stage Distribution ────────────────────────────────────────────── --}}
@if($isAdmin)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach($userStats as $stat)
    @if($stat['total'] > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-2.5 mb-4">
            <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($stat['user']->name, 0, 1)) }}
            </div>
            <p class="text-sm font-semibold text-gray-700">{{ $stat['user']->name }}</p>
            <span class="text-xs text-gray-400 ml-auto">{{ $stat['total'] }} leads</span>
        </div>
        <div class="space-y-3">
            @foreach($stat['stage_dist'] as $group)
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1.5">{{ $group['pipeline']->name }}</p>
                <div class="space-y-1.5">
                    @foreach($group['stages'] as $row)
                    @php $pct = $stat['total'] > 0 ? round($row['count'] / $stat['total'] * 100) : 0; @endphp
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs text-gray-500 w-36 truncate flex-shrink-0">{{ $row['stage']->name }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all"
                                 style="width:{{ $pct }}%; background:{{ $row['stage']->color ?? '#6366f1' }}"></div>
                        </div>
                        <span class="text-xs tabular-nums text-gray-500 w-14 text-right flex-shrink-0">
                            {{ $row['count'] }} <span class="text-gray-300">({{ $pct }}%)</span>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach
</div>
@else
{{-- Member: own stage distribution --}}
@php $stat = $userStats->first(); @endphp
@if($stat && $stat['total'] > 0)
<div class="bg-white rounded-xl border border-gray-200 p-5">
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">My Leads by Stage</h3>
    <div class="space-y-4">
        @foreach($stat['stage_dist'] as $group)
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">{{ $group['pipeline']->name }}</p>
            <div class="space-y-2">
                @foreach($group['stages'] as $row)
                @php $pct = $stat['total'] > 0 ? round($row['count'] / $stat['total'] * 100) : 0; @endphp
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 w-40 truncate flex-shrink-0">{{ $row['stage']->name }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             style="width:{{ $pct }}%; background:{{ $row['stage']->color ?? '#6366f1' }}"></div>
                    </div>
                    <span class="text-sm tabular-nums font-medium text-gray-700 w-6 text-right flex-shrink-0">{{ $row['count'] }}</span>
                    <span class="text-xs text-gray-400 w-9 text-right flex-shrink-0">{{ $pct }}%</span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endif

@endsection
