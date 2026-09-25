@extends('layouts.app')

@section('title', 'Tasks')
@section('heading', 'Tasks')

@section('content')
@php
function taskNavUrl(array $overrides): string {
    $params = array_merge(request()->query(), $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null && $v !== []);
    return route('tasks.index') . (count($params) ? '?' . http_build_query($params) : '');
}
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-6"
     x-data="taskPage('{{ csrf_token() }}')">

    {{-- ===== ADMIN FILTER BAR ===== --}}
    @if($isInternalAdmin)
    <form method="GET" action="{{ route('tasks.index') }}" class="flex items-center gap-2 flex-wrap">
        <input type="hidden" name="view"         value="{{ $viewMode }}">
        <input type="hidden" name="week_offset"  value="{{ $weekOffset }}">
        <input type="hidden" name="month_offset" value="{{ $monthOffset }}">

        {{-- Assigned by dropdown --}}
        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
            <button type="button" @click.stop="open = !open"
                    class="flex items-center gap-1.5 text-xs border rounded-lg px-2.5 py-1.5 bg-white {{ !empty($filterAssignedBy) ? 'border-indigo-400 text-indigo-700 bg-indigo-50' : 'border-gray-300 text-gray-600 hover:border-gray-400' }} transition-colors">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </svg>
                Assigned by
                @if(!empty($filterAssignedBy))
                <span class="bg-indigo-600 text-white rounded-full px-1.5 py-0.5 text-[10px] font-semibold">{{ count($filterAssignedBy) }}</span>
                @endif
                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="absolute z-30 top-full mt-1 left-0 bg-white border border-gray-200 rounded-lg shadow-lg min-w-[200px] max-h-52 overflow-y-auto py-1">
                @foreach($filterUsers as $u)
                <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="assigned_by[]" value="{{ $u->id }}"
                           {{ in_array($u->id, $filterAssignedBy) ? 'checked' : '' }}
                           class="w-3.5 h-3.5 rounded border-gray-300 text-indigo-600">
                    <span class="text-xs text-gray-700">{{ $u->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Assigned to dropdown --}}
        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
            <button type="button" @click.stop="open = !open"
                    class="flex items-center gap-1.5 text-xs border rounded-lg px-2.5 py-1.5 bg-white {{ !empty($filterAssignedTo) ? 'border-purple-400 text-purple-700 bg-purple-50' : 'border-gray-300 text-gray-600 hover:border-gray-400' }} transition-colors">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                </svg>
                Assigned to
                @if(!empty($filterAssignedTo))
                <span class="bg-purple-600 text-white rounded-full px-1.5 py-0.5 text-[10px] font-semibold">{{ count($filterAssignedTo) }}</span>
                @endif
                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
            <div x-show="open" x-cloak class="absolute z-30 top-full mt-1 left-0 bg-white border border-gray-200 rounded-lg shadow-lg min-w-[200px] max-h-52 overflow-y-auto py-1">
                @foreach($filterUsers as $u)
                <label class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="assigned_to[]" value="{{ $u->id }}"
                           {{ in_array($u->id, $filterAssignedTo) ? 'checked' : '' }}
                           class="w-3.5 h-3.5 rounded border-gray-300 text-purple-600">
                    <span class="text-xs text-gray-700">{{ $u->name }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <button type="submit"
                class="text-xs bg-indigo-600 text-white rounded-lg px-3 py-1.5 hover:bg-indigo-700 transition-colors">
            Apply
        </button>
        @if($hasFilters)
        <a href="{{ taskNavUrl(['assigned_by' => [], 'assigned_to' => []]) }}"
           class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Clear</a>
        @endif
    </form>
    @endif

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

    {{-- ===== CALENDAR (WEEK / MONTH) ===== --}}
    <div>
        {{-- Calendar header --}}
        <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
            {{-- Prev / Label / Next --}}
            @if($viewMode === 'week')
            <div class="flex items-center gap-1">
                <a href="{{ taskNavUrl(['week_offset' => $weekOffset - 1]) }}"
                   class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 19.5-7.5-7.5 7.5-7.5"/>
                    </svg>
                </a>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">
                    {{ $weekOffset === 0 ? 'This Week' : ($weekOffset === 1 ? 'Next Week' : ($weekOffset === -1 ? 'Last Week' : 'Week of')) }}
                    <span class="font-normal text-gray-400 normal-case tracking-normal ml-1">
                        {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
                    </span>
                </span>
                <a href="{{ taskNavUrl(['week_offset' => $weekOffset + 1]) }}"
                   class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
                @if($weekOffset !== 0)
                <a href="{{ taskNavUrl(['week_offset' => 0]) }}"
                   class="text-[11px] text-indigo-500 hover:text-indigo-700 border border-indigo-200 rounded-lg px-2 py-0.5 ml-1 transition-colors">Today</a>
                @endif
            </div>
            @else
            <div class="flex items-center gap-1">
                <a href="{{ taskNavUrl(['month_offset' => $monthOffset - 1]) }}"
                   class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 19.5-7.5-7.5 7.5-7.5"/>
                    </svg>
                </a>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">
                    {{ $monthStart->format('F Y') }}
                </span>
                <a href="{{ taskNavUrl(['month_offset' => $monthOffset + 1]) }}"
                   class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </a>
                @if($monthOffset !== 0)
                <a href="{{ taskNavUrl(['month_offset' => 0]) }}"
                   class="text-[11px] text-indigo-500 hover:text-indigo-700 border border-indigo-200 rounded-lg px-2 py-0.5 ml-1 transition-colors">This Month</a>
                @endif
            </div>
            @endif

            {{-- Week / Month toggle --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
                <a href="{{ taskNavUrl(['view' => 'week']) }}"
                   class="text-xs px-3 py-1 rounded-md transition-colors {{ $viewMode === 'week' ? 'bg-white text-gray-700 shadow-sm font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                    Week
                </a>
                <a href="{{ taskNavUrl(['view' => 'month']) }}"
                   class="text-xs px-3 py-1 rounded-md transition-colors {{ $viewMode === 'month' ? 'bg-white text-gray-700 shadow-sm font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                    Month
                </a>
            </div>
        </div>

        {{-- ===== WEEK VIEW ===== --}}
        @if($viewMode === 'week')
        <div class="overflow-x-auto pb-2">
        <div class="flex gap-2" style="min-width: max-content;">
            @foreach($weekDays as $day)
            @php
                $isToday  = $day['date']->isToday();
                $isPast   = $day['date']->isPast() && !$isToday;
            @endphp
            <div class="rounded-xl border {{ $isToday ? 'border-indigo-300 shadow-sm shadow-indigo-50' : 'border-gray-200' }} overflow-hidden bg-white flex flex-col min-h-[140px]" style="width: 180px; flex-shrink: 0;">
                <div class="px-2.5 py-2 border-b {{ $isToday ? 'bg-indigo-600 border-indigo-600' : 'bg-gray-50 border-gray-100' }}">
                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ $isToday ? 'text-indigo-200' : 'text-gray-400' }}">{{ $day['date']->format('D') }}</p>
                    <p class="text-sm font-bold {{ $isToday ? 'text-white' : ($isPast ? 'text-gray-300' : 'text-gray-700') }}">{{ $day['date']->format('d') }}</p>
                </div>
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
                            <p class="text-[11px] leading-snug {{ $task['is_done'] ? 'line-through text-gray-300' : 'text-gray-700' }} truncate" title="{{ $task['title'] }}">{{ $task['title'] }}</p>
                            <a href="{{ $task['context_url'] }}"
                               class="text-[10px] {{ $task['type'] === 'lead' ? 'text-indigo-400' : 'text-purple-400' }} hover:underline truncate block leading-tight">
                                {{ Str::limit($task['context'], 20) }}
                            </a>
                            @if($task['due_at']->format('H:i') !== '00:00')
                            <span class="text-[10px] text-gray-400 leading-tight">{{ $task['due_at']->format('H:i') }}</span>
                            @endif
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

        {{-- ===== MONTH VIEW ===== --}}
        @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Day-of-week headers --}}
            <div class="grid grid-cols-7 border-b border-gray-100">
                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
                <div class="py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-400
                    {{ $loop->index >= 5 ? 'bg-gray-50' : '' }}">
                    {{ $dow }}
                </div>
                @endforeach
            </div>
            {{-- Weeks --}}
            @foreach($monthGrid as $week)
            <div class="grid grid-cols-7 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                @foreach($week as $day)
                @php
                    $isToday   = $day['date']->isToday();
                    $inMonth   = $day['inMonth'];
                    $isWeekend = $day['date']->dayOfWeek === 0 || $day['date']->dayOfWeek === 6;
                    $visibleTasks = $day['tasks']->take(3);
                    $extraCount   = $day['tasks']->count() - 3;
                @endphp
                <div class="p-1.5 min-h-[90px] border-r border-gray-100 last:border-r-0
                    {{ $isWeekend ? 'bg-gray-50/50' : 'bg-white' }}
                    {{ !$inMonth ? 'opacity-40' : '' }}">
                    <div class="flex justify-center mb-1">
                        <span class="text-[11px] font-semibold w-5 h-5 flex items-center justify-center rounded-full
                            {{ $isToday ? 'bg-indigo-600 text-white' : ($inMonth ? 'text-gray-700' : 'text-gray-400') }}">
                            {{ $day['date']->format('j') }}
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        @foreach($visibleTasks as $task)
                        <div class="flex items-center gap-1 group">
                            <button type="button"
                                    @click="toggle('{{ $task['toggle_url'] ?? '' }}', $event)"
                                    class="w-2.5 h-2.5 flex-shrink-0 rounded-sm border {{ $task['is_done'] ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 hover:border-indigo-400' }} flex items-center justify-center transition-all">
                                @if($task['is_done'])
                                <svg class="w-1.5 h-1.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                                @endif
                            </button>
                            <a href="{{ $task['context_url'] }}"
                               class="text-[10px] leading-tight truncate {{ $task['is_done'] ? 'line-through text-gray-300' : ($task['type'] === 'lead' ? 'text-indigo-600' : 'text-purple-600') }} hover:underline"
                               title="{{ $task['title'] }}">
                                {{ Str::limit($task['title'], 18) }}
                            </a>
                        </div>
                        @endforeach
                        @if($extraCount > 0)
                        <span class="text-[10px] text-gray-400 pl-3.5">+{{ $extraCount }} more</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
        @endif
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
    @if($overdue->isEmpty() && $upcoming->isEmpty() && $noDue->isEmpty() && $done->isEmpty()
        && collect($weekDays)->every(fn($d) => $d['tasks']->isEmpty()))
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
