@extends('layouts.app')

@section('title', 'Daily Report')
@section('heading', 'Daily Report')

@section('content')

@php
$typeLabel = [
    'note'                 => 'Note added',
    'task_created'         => 'Task created',
    'whatsapp_incoming'    => 'WA received',
    'whatsapp_outgoing'    => 'WA sent',
    'stage_changed'        => 'Stage changed',
    'sp_changed'           => 'Service provider changed',
    'agent_changed'        => 'Agent changed',
    'assigned'             => 'Assignee changed',
    'tag_added'            => 'Tag added',
    'tag_removed'          => 'Tag removed',
    'tags_updated'         => 'Tags updated',
    'custom_field_updated' => 'Field updated',
];

// color: bg dot class (Tailwind—must be inline style for purge safety)
$typeColor = [
    'note'                 => '#6366f1',
    'task_created'         => '#8b5cf6',
    'whatsapp_incoming'    => '#22c55e',
    'whatsapp_outgoing'    => '#10b981',
    'stage_changed'        => '#f59e0b',
    'sp_changed'           => '#3b82f6',
    'agent_changed'        => '#3b82f6',
    'assigned'             => '#0ea5e9',
    'tag_added'            => '#ec4899',
    'tag_removed'          => '#f43f5e',
    'tags_updated'         => '#ec4899',
    'custom_field_updated' => '#6b7280',
];
@endphp

{{-- Date nav + user filter --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">

    {{-- Date navigation --}}
    <div class="flex items-center gap-1">
        <a href="{{ route('reports.daily', ['date' => $prevDate] + ($filterUserId && $isAdmin ? ['user_id' => $filterUserId] : [])) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
        </a>

        <div class="px-4 py-1.5 rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-800 min-w-[180px] text-center">
            {{ $date->format('d F Y') }}
            @if($isToday)
            <span class="ml-1.5 text-xs font-medium text-indigo-600">(Today)</span>
            @endif
        </div>

        @if(!$isToday)
        <a href="{{ route('reports.daily', ['date' => $nextDate] + ($filterUserId && $isAdmin ? ['user_id' => $filterUserId] : [])) }}"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
        @else
        <span class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-100 bg-gray-50 text-sm text-gray-300 cursor-not-allowed">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </span>
        @endif

        {{-- Date picker --}}
        <form method="GET" action="{{ route('reports.daily') }}" class="ml-2 flex items-center gap-1.5">
            @if($filterUserId && $isAdmin)
            <input type="hidden" name="user_id" value="{{ $filterUserId }}">
            @endif
            <input type="date" name="date" value="{{ $date->toDateString() }}"
                   class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
            <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Go</button>
        </form>
    </div>

    {{-- User filter (admin only) --}}
    @if($isAdmin)
    <form method="GET" action="{{ route('reports.daily') }}" class="flex items-center gap-2">
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <label class="text-xs text-gray-500 font-medium">User:</label>
        <select name="user_id"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
            <option value="">All Users</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" {{ $filterUserId === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </form>
    @endif

</div>

{{-- Summary stat bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
    @php
    $stats = [
        ['label' => 'New Leads',      'value' => $newLeads->count(),     'color' => '#6366f1'],
        ['label' => 'Leads Touched',  'value' => $totalLeadsTouched,     'color' => '#8b5cf6'],
        ['label' => 'WA Received',    'value' => $waIn,                  'color' => '#22c55e'],
        ['label' => 'WA Sent',        'value' => $waOut,                 'color' => '#10b981'],
        ['label' => 'Notes',          'value' => $notes->count(),        'color' => '#6366f1'],
        ['label' => 'Tasks Created',  'value' => $tasksCreated->count(), 'color' => '#8b5cf6'],
        ['label' => 'Stage Changes',  'value' => $stageChanges,          'color' => '#f59e0b'],
    ];
    @endphp
    @foreach($stats as $stat)
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold" style="color:{{ $stat['color'] }}">{{ $stat['value'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5 leading-tight">{{ $stat['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- NEW LEADS --}}
@if($newLeads->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-indigo-500 inline-block"></span>
        <h3 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">New Leads ({{ $newLeads->count() }})</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($newLeads as $lead)
        <a href="{{ route('leads.show', $lead) }}"
           class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
            <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ $lead->initials() }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $lead->fullName() }}</p>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    @if($lead->stage)
                    <span class="text-xs font-medium px-1.5 py-0.5 rounded-full text-white"
                          style="background:{{ $lead->stage->color }}">{{ $lead->stage->name }}</span>
                    @endif
                    @foreach($lead->tags->take(3) as $tag)
                    <span class="text-xs px-1.5 py-0.5 rounded-full text-white"
                          style="background:{{ $tag->color }}">{{ $tag->name }}</span>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 text-right">
                <div>
                    @if($lead->assignedTo)
                    <p class="text-xs text-gray-500">{{ $lead->assignedTo->name }}</p>
                    @else
                    <p class="text-xs text-gray-300">Unassigned</p>
                    @endif
                    <p class="text-xs text-gray-400">
                        {{ ucfirst(str_replace('_', ' ', $lead->source ?? 'manual')) }}
                    </p>
                </div>
                <span class="text-xs text-gray-400 w-12 text-right whitespace-nowrap">{{ $lead->created_at->format('H:i') }}</span>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- ACTIVITY FEED --}}
@if($feedByLead->isNotEmpty())
<div class="mb-6">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>
        <h3 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
            Activity Feed — {{ $feedByLead->count() }} lead{{ $feedByLead->count() !== 1 ? 's' : '' }}
        </h3>
    </div>

    <div class="space-y-3">
        @foreach($feedByLead as $leadId => $entries)
        @php $lead = $entries->first()['lead']; @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

            {{-- Lead header --}}
            <a href="{{ route('leads.show', $lead) }}"
               class="flex items-center gap-3 px-5 py-3 bg-gray-50 hover:bg-gray-100 transition-colors border-b border-gray-100">
                <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ $lead->initials() }}
                </div>
                <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-gray-800">{{ $lead->fullName() }}</span>
                    @if($lead->stage)
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                          style="background:{{ $lead->stage->color }}">{{ $lead->stage->name }}</span>
                    @endif
                    @if($lead->assignedTo)
                    <span class="text-xs text-gray-400">→ {{ $lead->assignedTo->name }}</span>
                    @endif
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0">{{ $entries->count() }} action{{ $entries->count() !== 1 ? 's' : '' }}</span>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </a>

            {{-- Activity entries --}}
            <div class="divide-y divide-gray-50">
                @foreach($entries as $entry)
                @php
                    $type  = $entry['type'];
                    $actor = $entry['actor'];
                    $item  = $entry['item'];
                    $color = $typeColor[$type] ?? '#6b7280';
                    $label = $typeLabel[$type] ?? ucwords(str_replace('_', ' ', $type));
                @endphp
                <div class="flex items-start gap-3 px-5 py-3">
                    {{-- Time --}}
                    <span class="text-xs text-gray-400 w-10 flex-shrink-0 mt-0.5 tabular-nums">
                        {{ $entry['sort_at']->format('H:i') }}
                    </span>

                    {{-- Type dot --}}
                    <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0"
                          style="background:{{ $color }}"></span>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-semibold" style="color:{{ $color }}">{{ $label }}</span>
                            @if($actor)
                            <span class="text-xs text-gray-400">by {{ $actor->name }}</span>
                            @elseif($type === 'whatsapp_incoming')
                            <span class="text-xs text-gray-400">from contact</span>
                            @endif
                        </div>

                        @if($type === 'note')
                        <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">{{ $item->content }}</p>
                        @elseif($type === 'task_created')
                        <p class="text-xs text-gray-600 mt-0.5">
                            "{{ $item->title }}"
                            @if($item->assignedTo)
                            → assigned to {{ $item->assignedTo->name }}
                            @endif
                            @if($item->due_at)
                            · due {{ $item->due_at->format('d M') }}
                            @endif
                        </p>
                        @elseif(in_array($type, ['whatsapp_incoming', 'whatsapp_outgoing']))
                        <p class="text-xs text-gray-600 mt-0.5 line-clamp-1">{{ $item->description }}</p>
                        @else
                        <p class="text-xs text-gray-600 mt-0.5">{{ $item->description }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="bg-white rounded-xl border border-gray-200 px-5 py-12 text-center mb-6">
    <p class="text-sm text-gray-400">No activity recorded for this day.</p>
</div>
@endif

{{-- STUCK LEADS --}}
@if($stuckLeads->isNotEmpty())
<div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-amber-100 bg-amber-50 flex items-center gap-2">
        <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        <h3 class="text-xs font-semibold text-amber-700 uppercase tracking-wide">
            Stuck Leads — No stage change in 7+ days ({{ $stuckLeads->count() }})
        </h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($stuckLeads as $lead)
        @php $days = now()->diffInDays(\Carbon\Carbon::parse($lead->last_changed)); @endphp
        <a href="{{ route('leads.show', $lead) }}"
           class="flex items-center gap-3 px-5 py-3 hover:bg-amber-50/40 transition-colors">
            <div class="w-7 h-7 rounded-full bg-amber-400 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ $lead->initials() }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $lead->fullName() }}</p>
                @if($lead->stage)
                <span class="text-xs font-medium px-1.5 py-0.5 rounded-full text-white inline-block mt-0.5"
                      style="background:{{ $lead->stage->color }}">{{ $lead->stage->name }}</span>
                @endif
            </div>
            <div class="flex-shrink-0 text-right">
                <p class="text-sm font-semibold text-amber-600">{{ $days }}d</p>
                <p class="text-xs text-gray-400">{{ $lead->assignedTo?->name ?? 'Unassigned' }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@endsection
