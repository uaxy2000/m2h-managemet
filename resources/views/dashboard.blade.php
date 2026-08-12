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
@if($ownOnly)
<div class="flex items-center gap-1.5 text-xs text-indigo-600 font-medium mb-3">
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
    </svg>
    Showing your assigned leads only
</div>
@endif
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <a href="{{ route('leads.index') }}"
       class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 transition-colors">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $ownOnly ? 'My Leads' : 'Total Leads' }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalLeads }}</p>
        <p class="text-xs text-indigo-600 mt-1.5">View all →</p>
    </a>

    <a href="{{ route('leads.index', ['source' => 'meta_ad']) }}"
       class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 transition-colors">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Meta Ad Leads</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $metaLeads }}</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ $totalLeads > 0 ? round($metaLeads / $totalLeads * 100) : 0 }}% of {{ $ownOnly ? 'my' : 'total' }}</p>
    </a>

    <a href="{{ route('tasks.index') }}"
       class="bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 transition-colors">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Open Tasks</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $openTasks }}</p>
        <div class="mt-1.5 flex flex-col gap-1">
            @if($todayTasks > 0)
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#d97706;animation:pulse-dot 1.2s ease-in-out infinite"></span>
                TODAY'S TASKS {{ $todayTasks }}
            </span>
            @endif
            @if($overdueTasks > 0)
            <span class="text-xs font-semibold text-red-600">
                {{ $overdueTasks }} OVERDUE
            </span>
            @endif
            @if($todayTasks === 0 && $overdueTasks === 0)
            <span class="text-xs text-indigo-600">View tasks →</span>
            @endif
        </div>
    </a>

    @once
    <style>
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .4; transform: scale(.65); }
    }
    </style>
    @endonce

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

{{-- Recent Leads --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recent Leads</h3>
        <a href="{{ route('leads.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all →</a>
    </div>
    @if($recentLeads->isEmpty())
    <div class="px-5 py-8 text-center">
        <p class="text-sm text-gray-400">No leads yet.</p>
        <a href="{{ route('leads.create') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 inline-block">Add your first lead →</a>
    </div>
    @else
    <div class="divide-y divide-gray-50">
        @foreach($recentLeads as $lead)
        <a href="{{ route('leads.show', $lead) }}"
           class="flex items-center gap-3 px-5 py-2.5 hover:bg-gray-50 transition-colors">
            <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ $lead->initials() }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $lead->fullName() }}</p>
                    @if($lead->is_duplicate_flag)
                    <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full flex-shrink-0">dup</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                @if($lead->stage)
                <span class="text-xs font-medium px-2 py-0.5 rounded-full text-white" style="background:{{ $lead->stage->color }}">
                    {{ $lead->stage->name }}
                </span>
                @endif
                @foreach($lead->tags->take(2) as $tag)
                <span class="text-xs px-2 py-0.5 rounded-full text-white" style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                @endforeach
                @if($lead->assignedTo)
                <span class="text-xs text-gray-400">{{ $lead->assignedTo->name }}</span>
                @endif
                <span class="text-xs text-gray-300 w-16 text-right">{{ $lead->created_at->diffForHumans(null, true, true) }}</span>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>

{{-- Build Progress --}}
@php
$done = 'w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0';
$todo = 'w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0';
$checkIcon = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>';
$clockIcon = '<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg>';
$phases = [
    [
        'title' => 'Core CRM',
        'items' => [
            ['label' => 'Database schema & migrations',                                    'complete' => true],
            ['label' => 'Authentication (login / logout)',                                  'complete' => true],
            ['label' => 'User roles — super_admin, admin, user',                           'complete' => true],
            ['label' => 'Company management (internal / service_provider / agent types)',   'complete' => true],
            ['label' => 'User management & company assignment',                            'complete' => true],
            ['label' => 'Pipeline configuration',                                          'complete' => true],
            ['label' => 'Stage & sub-stage configuration',                                 'complete' => true],
            ['label' => 'Lead CRUD (create, view, edit, delete)',                          'complete' => true],
            ['label' => 'Kanban board (drag & drop between stages)',                       'complete' => true],
            ['label' => 'Lead stage history tracking',                                     'complete' => true],
            ['label' => 'Duplicate lead detection (email + phone)',                        'complete' => true],
            ['label' => 'Notes on leads with visibility control (internal / shared)',      'complete' => true],
            ['label' => 'Tasks on leads (with completion toggle)',                         'complete' => true],
            ['label' => 'Programs (settings + lead attachment + primary flag)',            'complete' => true],
            ['label' => 'Tags with group categorization (Country, Status, etc.)',          'complete' => true],
            ['label' => 'Lead assignment to internal users',                               'complete' => true],
            ['label' => 'Service Provider & Agent linking per lead',                       'complete' => true],
            ['label' => 'Lead filters (search, source, tags, program, duplicate, custom fields)', 'complete' => true],
            ['label' => 'Internal member lead scoping (own leads only — dashboard, kanban, filter)', 'complete' => true],
            ['label' => 'Custom fields with per-lead values & Meta form mapping',          'complete' => true],
            ['label' => 'Lead timeline — notes, tasks, tag, assignment & custom field changes', 'complete' => true],
            ['label' => 'Kanban enhancements (creation date, WA pulse, tag tooltips, avatar, stage change from detail)', 'complete' => true],
            ['label' => 'Kanban lazy load — 100 cards/stage, scroll to load more, shown/total badge', 'complete' => true],
            ['label' => 'Kanban scroll + filter state restore via sessionStorage (back from lead detail)', 'complete' => true],
            ['label' => 'Kanban unread WA leads sorted to top of stage column', 'complete' => true],
            ['label' => 'Inbox — full WA history (read + unread), unread pinned, paginated', 'complete' => true],
            ['label' => 'Lead timeline — kanban drag-drop stage changes now logged',        'complete' => true],
            ['label' => 'Lead note textarea — 2× taller, manually resizable',              'complete' => true],
            ['label' => 'Client portal login (read-only lead view)',                       'complete' => false],
        ],
    ],
    [
        'title' => 'Integrations',
        'items' => [
            ['label' => 'Meta Lead Ads — page connection & token management',              'complete' => true],
            ['label' => 'Meta Lead Ads — form mapping (pipeline, stage, tags, assignee)', 'complete' => true],
            ['label' => 'Meta Lead Ads — webhook receiver (HMAC signature validation)',   'complete' => true],
            ['label' => 'Meta Lead Ads — auto lead import (incl. Turkish field names)',   'complete' => true],
            ['label' => 'Meta Lead Ads — custom form question capture & field mapping',   'complete' => true],
            ['label' => 'Meta Lead Ads — bulk import of existing leads via Graph API',    'complete' => true],
            ['label' => 'Meta Ads — leads.meta_adset_id capture from webhook',             'complete' => true],
            ['label' => 'Meta Ads — Marketing API insights sync (account / campaign / adset / ad)', 'complete' => true],
            ['label' => 'Meta Ads — /reports/meta-ads: hierarchy table, spend, leads, CPL, CTR, daily chart', 'complete' => true],
            ['label' => 'Meta Ads — AJAX backfill with progress bar (no timeout)',         'complete' => true],
            ['label' => 'Meta Ads — ad creative preview modal (Mobile / Desktop / Instagram)', 'complete' => true],
            ['label' => 'Meta Ads — "View Leads" drill-down from campaign / adset / ad',  'complete' => true],
            ['label' => 'WhatsApp — send & receive messages per lead (incl. templates, sent/delivered/read ticks)', 'complete' => true],
            ['label' => 'WhatsApp — template variable mapping ({{1}} → name etc. via Settings)', 'complete' => true],
            ['label' => 'Google Form pre-fill from lead note (partner referral form)',    'complete' => true],
            ['label' => 'Kommo CRM → M2H migration (leads, tags, notes)',                'complete' => true],
            ['label' => 'Google Form → CRM (auto lead import)',                           'complete' => false],
            ['label' => 'Google Sheets sync',                                             'complete' => false],
            ['label' => 'Email integration (send & receive per lead)',                    'complete' => false],
            ['label' => 'SMS integration (Twilio / Netgsm or similar)',                   'complete' => false],
        ],
    ],
    [
        'title' => 'Segments & Outreach',
        'items' => [
            ['label' => 'Segment builder (filter by pipeline, stage, tags, source, country, date)', 'complete' => false],
            ['label' => 'Saved segments (name, list, edit, delete)',                       'complete' => false],
            ['label' => 'WhatsApp bulk message to segment',                               'complete' => false],
            ['label' => 'Email bulk message to segment',                                  'complete' => false],
            ['label' => 'SMS bulk message to segment',                                    'complete' => false],
        ],
    ],
    [
        'title' => 'Access Control',
        'items' => [
            ['label' => 'Role-based kanban column & stage visibility (Lead Received restricted to admins)', 'complete' => true],
            ['label' => 'Service provider login (read-only view of assigned leads)',       'complete' => false],
            ['label' => 'Agent login (read-only view of assigned leads)',                  'complete' => false],
            ['label' => 'Role-based field visibility',                                    'complete' => false],
        ],
    ],
    [
        'title' => 'Collaboration',
        'items' => [
            ['label' => 'Boards — cards, notes & tasks with read/write access control',  'complete' => true],
            ['label' => 'Board unread tracking (sidebar badge, per-card indicator)',      'complete' => true],
            ['label' => 'Task assignment restricted to board members',                    'complete' => true],
            ['label' => 'Tasks — unified weekly view across leads & boards',              'complete' => true],
            ['label' => 'ToDo Lists — CRUD, member access, items with who/when audit, copy to clipboard', 'complete' => true],
            ['label' => 'ToDo List ↔ Board linking (board view shows linked lists & allows adding items)', 'complete' => true],
        ],
    ],
    [
        'title' => 'Productivity & Reporting',
        'items' => [
            ['label' => 'In-app notifications',                                           'complete' => false],
            ['label' => 'Email notifications (lead assigned, stage changed)',              'complete' => false],
            ['label' => 'Lead bulk actions (assign, tag, move stage)',                    'complete' => false],
            ['label' => 'Reporting & analytics — stage, source, assignee, monthly trend (initial)', 'complete' => true],
            ['label' => 'Performance tracking — per-user registration stats, conversion rate, avg days, monthly breakdown', 'complete' => true],
            ['label' => 'Commission / payment tracking per registered lead',             'complete' => true],
            ['label' => 'Dashboard — today\'s tasks count & overdue indicator in Open Tasks card', 'complete' => true],
            ['label' => 'Daily Activity Report — date nav, user filter, feed by lead, stuck leads', 'complete' => true],
            ['label' => 'Dashboard stat cards (live messages, sync status)',              'complete' => false],
        ],
    ],
];
$allItems = collect($phases)->flatMap(fn($p) => $p['items']);
$completedCount = $allItems->where('complete', true)->count();
$totalCount = $allItems->count();
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-semibold text-gray-700">Build Progress</h3>
        <span class="text-xs text-gray-500">{{ $completedCount }} / {{ $totalCount }} complete</span>
    </div>
    <div class="w-full bg-gray-100 rounded-full h-1.5 mb-6">
        <div class="bg-indigo-500 h-1.5 rounded-full transition-all"
             style="width: {{ round($completedCount / $totalCount * 100) }}%"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-10">
        @foreach($phases as $phase)
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3 pb-2 border-b border-gray-200">{{ $phase['title'] }}</p>
            <ul class="space-y-2">
                @foreach($phase['items'] as $item)
                <li class="flex items-center gap-2.5 text-sm">
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
        @endforeach
    </div>
</div>

@endsection
