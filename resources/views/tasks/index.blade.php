@extends('layouts.app')

@section('title', 'Tasks')
@section('heading', 'Tasks')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6"
     x-data="taskPage('{{ csrf_token() }}')">

    {{-- ===== OVERDUE ===== --}}
    @if($overdue->isNotEmpty())
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-red-500">Overdue</span>
            <span class="text-xs font-semibold bg-red-100 text-red-600 px-2 py-0.5 rounded-full">{{ $overdue->count() }}</span>
        </div>
        <div class="bg-white rounded-xl border border-red-200 divide-y divide-red-50 overflow-hidden">
            @foreach($overdue as $task)
            @include('tasks._row', ['task' => $task, 'accent' => 'red'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== WEEKLY PLAN ===== --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                This Week
                <span class="font-normal text-gray-400 ml-1 normal-case tracking-normal">
                    {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
                </span>
            </span>
        </div>

        <div class="overflow-x-auto pb-2">
        <div class="flex gap-2" style="min-width: max-content;">
            @foreach($weekDays as $day)
            @php
                $isToday   = $day['date']->isToday();
                $isPast    = $day['date']->isPast() && !$isToday;
                $hasTasks  = $day['tasks']->isNotEmpty();
            @endphp
            <div class="rounded-xl border {{ $isToday ? 'border-indigo-300 shadow-sm shadow-indigo-50' : 'border-gray-200' }} overflow-hidden bg-white flex flex-col min-h-[140px]" style="width: 180px; flex-shrink: 0;">
                {{-- Day header --}}
                <div class="px-2.5 py-2 border-b {{ $isToday ? 'bg-indigo-600 border-indigo-600' : ($isPast ? 'bg-gray-50 border-gray-100' : 'bg-gray-50 border-gray-100') }}">
                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ $isToday ? 'text-indigo-200' : 'text-gray-400' }}">
                        {{ $day['date']->format('D') }}
                    </p>
                    <p class="text-sm font-bold {{ $isToday ? 'text-white' : ($isPast ? 'text-gray-300' : 'text-gray-700') }}">
                        {{ $day['date']->format('d') }}
                    </p>
                </div>
                {{-- Tasks --}}
                <div class="flex-1 p-1.5 space-y-1">
                    @forelse($day['tasks'] as $task)
                    <div class="flex items-start gap-1.5 group">
                        <button type="button"
                                @click="toggle('{{ $task['toggle_url'] ?? '' }}', $event)"
                                class="mt-0.5 w-3.5 h-3.5 flex-shrink-0 rounded-sm border {{ $task['is_done'] ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 hover:border-indigo-400' }} flex items-center justify-center transition-all">
                            @if($task['is_done'])
                            <svg class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            @endif
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] leading-snug {{ $task['is_done'] ? 'line-through text-gray-300' : 'text-gray-700' }} truncate"
                               title="{{ $task['title'] }}">{{ $task['title'] }}</p>
                            <a href="{{ $task['context_url'] }}"
                               class="text-[10px] {{ $task['type'] === 'lead' ? 'text-indigo-400' : 'text-purple-400' }} hover:underline truncate block leading-tight">
                                {{ Str::limit($task['context'], 20) }}
                            </a>
                        </div>
                    </div>
                    @empty
                    @if(!$isPast)
                    <p class="text-[10px] text-gray-300 text-center py-2">–</p>
                    @endif
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
        </div>
    </div>

    {{-- ===== UPCOMING ===== --}}
    @if($upcoming->isNotEmpty())
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Upcoming</span>
            <span class="text-xs font-semibold bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $upcoming->count() }}</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50 overflow-hidden">
            @foreach($upcoming as $task)
            @include('tasks._row', ['task' => $task, 'accent' => 'gray'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== NO DUE DATE ===== --}}
    @if($noDue->isNotEmpty())
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">No due date</span>
            <span class="text-xs font-semibold bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full">{{ $noDue->count() }}</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50 overflow-hidden">
            @foreach($noDue as $task)
            @include('tasks._row', ['task' => $task, 'accent' => 'gray'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== RECENTLY DONE ===== --}}
    @if($done->isNotEmpty())
    <div x-data="{ open: false }">
        <button @click="open = !open" type="button"
                class="flex items-center gap-2 text-xs text-gray-400 hover:text-gray-600 transition-colors mb-3">
            <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
            <span class="font-bold uppercase tracking-wider">Recently completed</span>
            <span class="font-semibold bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full">{{ $done->count() }}</span>
        </button>
        <div x-show="open" x-cloak>
            <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50 overflow-hidden opacity-60">
                @foreach($done as $task)
                @include('tasks._row', ['task' => $task, 'accent' => 'gray'])
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if($overdue->isEmpty() && $upcoming->isEmpty() && $noDue->isEmpty() && $done->isEmpty() && collect($weekDays)->every(fn($d) => $d['tasks']->isEmpty()))
    <div class="text-center py-16 text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        <p class="text-sm font-medium">No tasks found</p>
        <p class="text-xs mt-1">Tasks from leads and boards will appear here once a due date is set.</p>
    </div>
    @endif

</div>

<script>
function taskPage(csrf) {
    return {
        toggle(url, event) {
            if (!url) return;
            const btn = event.currentTarget;
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                .then(r => r.json())
                .then(() => window.location.reload());
        },
    };
}
</script>
@endsection
