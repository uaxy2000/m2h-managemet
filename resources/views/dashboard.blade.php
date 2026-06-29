@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

{{-- Welcome banner --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-indigo-500 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Signed in as
                <span class="font-medium text-indigo-600">{{ auth()->user()->email }}</span>
                &nbsp;·&nbsp;
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ auth()->user()->roleBadgeColor() }}">
                    {{ auth()->user()->roleLabel() }}
                </span>
            </p>
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Leads</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $totalLeads }}</p>
        <a href="{{ route('leads.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 mt-1 inline-block">View all →</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Open Tasks</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $openTasks }}</p>
        <p class="text-xs text-gray-400 mt-1">Across all leads</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Messages Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
        <p class="text-xs text-gray-400 mt-1">WhatsApp not yet connected</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sheets Synced</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">—</p>
        <p class="text-xs text-gray-400 mt-1">Sync not yet configured</p>
    </div>

</div>

{{-- Phase 1 progress --}}
@php
$done = 'w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0';
$todo = 'w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0';
$checkIcon = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>';
$clockIcon = '<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>';
$items = [
    ['label' => 'Database migrations',                   'complete' => true],
    ['label' => 'Authentication & user roles',            'complete' => true],
    ['label' => 'Pipeline, stage & sub-stage configuration', 'complete' => true],
    ['label' => 'Lead CRUD + kanban board',               'complete' => true],
    ['label' => 'Notes & tasks on leads',                 'complete' => true],
    ['label' => 'Programs (settings + lead attachment)',  'complete' => true],
    ['label' => 'Tags (lead categorization + filter)',    'complete' => true],
    ['label' => 'WhatsApp integration',                   'complete' => false],
    ['label' => 'Google Sheets sync',                     'complete' => false],
    ['label' => 'Notifications (in-app + email)',         'complete' => false],
];
$completedCount = collect($items)->where('complete', true)->count();
$totalCount = count($items);
@endphp
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-semibold text-gray-700">Phase 1 — Build Progress</h3>
        <span class="text-xs text-gray-500">{{ $completedCount }} / {{ $totalCount }}</span>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-1.5 mb-5">
        <div class="bg-indigo-500 h-1.5 rounded-full transition-all"
             style="width: {{ round($completedCount / $totalCount * 100) }}%"></div>
    </div>
    <ul class="space-y-2.5">
        @foreach($items as $item)
        <li class="flex items-center gap-3 text-sm">
            <span class="{{ $item['complete'] ? $done : $todo }}">
                {!! $item['complete'] ? $checkIcon : $clockIcon !!}
            </span>
            <span class="{{ $item['complete'] ? 'text-gray-700' : 'text-gray-400' }}">
                {{ $item['label'] }}
            </span>
        </li>
        @endforeach
    </ul>
</div>

@endsection
