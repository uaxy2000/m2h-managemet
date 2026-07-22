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

{{-- Infrastructure notes --}}
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-amber-800 mb-1">Bekleyen Altyapı İşlemleri</p>
            <ul class="text-sm text-amber-700 space-y-1 list-disc list-inside">
                <li>GitHub deposu şu an <strong>public</strong> — SSH erişimli sunucuya geçince private yapılacak</li>
                <li>Otomatik deploy (webhook) mevcut sunucuda çalışmıyor — port 8443 dışarıya kapalı</li>
            </ul>
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

{{-- Build Progress --}}
@php
$done = 'w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0';
$todo = 'w-5 h-5 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center flex-shrink-0';
$checkIcon = '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>';
$clockIcon = '<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>';
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
            ['label' => 'Meta Lead Ads — custom form question capture',                   'complete' => true],
            ['label' => 'Meta Lead Ads — bulk import of existing leads via Graph API',    'complete' => false],
            ['label' => 'WhatsApp — send & receive messages per lead',                    'complete' => false],
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
            ['label' => 'Service provider login (read-only view of assigned leads)',       'complete' => false],
            ['label' => 'Agent login (read-only view of assigned leads)',                  'complete' => false],
            ['label' => 'Role-based field visibility',                                    'complete' => false],
        ],
    ],
    [
        'title' => 'Productivity & Reporting',
        'items' => [
            ['label' => 'In-app notifications',                                           'complete' => false],
            ['label' => 'Email notifications (lead assigned, stage changed)',              'complete' => false],
            ['label' => 'Lead bulk actions (assign, tag, move stage)',                    'complete' => false],
            ['label' => 'Reporting & analytics (leads by source, stage, period)',         'complete' => false],
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-2">
        @foreach($phases as $phase)
        <div class="{{ !$loop->first && !($loop->index === 1) ? 'mt-10 pt-6 border-t-2 border-gray-200' : '' }}">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-3">{{ $phase['title'] }}</p>
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
