@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

{{-- Welcome banner --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white text-base font-bold flex-shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="text-base font-semibold text-gray-800">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-sm text-gray-400 mt-0.5">
                {{ now()->format('l, d F Y') }}
                &nbsp;·&nbsp;
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ auth()->user()->roleBadgeColor() }}">
                    {{ auth()->user()->roleLabel() }}
                </span>
            </p>
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <a href="{{ route('leads.index') }}"
       class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 transition-colors">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Leads</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalLeads }}</p>
        <p class="text-xs text-indigo-600 mt-1.5">View all →</p>
    </a>

    <a href="{{ route('leads.index', ['source' => 'meta_ad']) }}"
       class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 transition-colors">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Meta Ad Leads</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $metaLeads }}</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ $totalLeads > 0 ? round($metaLeads / $totalLeads * 100) : 0 }}% of total</p>
    </a>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Open Tasks</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $openTasks }}</p>
        <p class="text-xs text-gray-400 mt-1.5">Pending across all leads</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">New This Week</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $newThisWeek }}</p>
        @if($duplicates > 0)
        <a href="{{ route('leads.index', ['duplicate' => '1']) }}"
           class="text-xs text-amber-600 hover:text-amber-800 mt-1.5 inline-block">
            {{ $duplicates }} duplicate{{ $duplicates > 1 ? 's' : '' }} to review →
        </a>
        @else
        <p class="text-xs text-gray-400 mt-1.5">Since {{ now()->startOfWeek()->format('d M') }}</p>
        @endif
    </div>

</div>

<div class="grid grid-cols-3 gap-5">

    {{-- Recent Leads --}}
    <div class="col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recent Leads</h3>
                <a href="{{ route('leads.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all →</a>
            </div>

            @if($recentLeads->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">No leads yet.</p>
                <a href="{{ route('leads.create') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 inline-block">Add your first lead →</a>
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentLeads as $lead)
                <a href="{{ route('leads.show', $lead) }}"
                   class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ $lead->initials() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $lead->fullName() }}</p>
                            @if($lead->is_duplicate_flag)
                            <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full flex-shrink-0">dup</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5">
                            @if($lead->stage)
                            <span style="color: {{ $lead->stage->color }}">{{ $lead->stage->name }}</span>
                            @if($lead->assignedTo) &nbsp;·&nbsp; {{ $lead->assignedTo->name }} @endif
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @foreach($lead->tags->take(2) as $tag)
                        <span class="text-xs px-2 py-0.5 rounded-full text-white" style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                        @endforeach
                        <span class="text-xs text-gray-300">{{ $lead->created_at->diffForHumans(null, true, true) }}</span>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Build Progress --}}
    @php
    $done = 'w-4 h-4 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0';
    $todo = 'w-4 h-4 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0';
    $checkIcon = '<svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>';
    $clockIcon = '<svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg>';
    $items = [
        ['label' => 'Pipeline, stage & lead management',       'complete' => true],
        ['label' => 'Kanban board with drag & drop',           'complete' => true],
        ['label' => 'Notes, tasks, tags & programs per lead',  'complete' => true],
        ['label' => 'Lead assignment & company linking',       'complete' => true],
        ['label' => 'Lead filters (search, source, tags…)',    'complete' => true],
        ['label' => 'Meta Lead Ads webhook & auto-import',     'complete' => true],
        ['label' => 'Custom fields with Meta form mapping',    'complete' => true],
        ['label' => 'Meta — bulk import existing leads',       'complete' => false],
        ['label' => 'WhatsApp send & receive per lead',        'complete' => false],
        ['label' => 'Client / agent / provider portals',       'complete' => false],
        ['label' => 'Segment builder & bulk outreach',         'complete' => false],
        ['label' => 'Reporting & analytics',                   'complete' => false],
    ];
    $done_count  = collect($items)->where('complete', true)->count();
    $total_count = count($items);
    @endphp

    <div class="col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Build Progress</h3>
                <span class="text-xs text-gray-400">{{ $done_count }}/{{ $total_count }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1 mb-4">
                <div class="bg-indigo-500 h-1 rounded-full" style="width: {{ round($done_count / $total_count * 100) }}%"></div>
            </div>
            <ul class="space-y-2">
                @foreach($items as $item)
                <li class="flex items-center gap-2 text-xs">
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
    </div>

</div>

@endsection
