@extends('layouts.app')
@section('title', 'Meetings')
@section('heading', 'Meetings')

@section('content')
@php
function meetingNavUrl(array $overrides): string {
    $params = array_merge(request()->query(), $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return route('meetings.index') . (count($params) ? '?' . http_build_query($params) : '');
}
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        {{-- Navigation --}}
        <div class="flex items-center gap-1">
            @if($viewMode === 'week')
            <a href="{{ meetingNavUrl(['week_offset' => $weekOffset - 1]) }}" class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 19.5-7.5-7.5 7.5-7.5"/></svg>
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">
                {{ $weekOffset === 0 ? 'This Week' : ($weekOffset === 1 ? 'Next Week' : ($weekOffset === -1 ? 'Last Week' : '')) }}
                <span class="font-normal text-gray-400 normal-case tracking-normal ml-1">{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            </span>
            <a href="{{ meetingNavUrl(['week_offset' => $weekOffset + 1]) }}" class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
            @if($weekOffset !== 0)
            <a href="{{ meetingNavUrl(['week_offset' => 0]) }}" class="text-[11px] text-indigo-500 hover:text-indigo-700 border border-indigo-200 rounded-lg px-2 py-0.5 ml-1">Today</a>
            @endif
            @else
            <a href="{{ meetingNavUrl(['month_offset' => $monthOffset - 1]) }}" class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 19.5-7.5-7.5 7.5-7.5"/></svg>
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">{{ $monthStart->format('F Y') }}</span>
            <a href="{{ meetingNavUrl(['month_offset' => $monthOffset + 1]) }}" class="p-1 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </a>
            @if($monthOffset !== 0)
            <a href="{{ meetingNavUrl(['month_offset' => 0]) }}" class="text-[11px] text-indigo-500 hover:text-indigo-700 border border-indigo-200 rounded-lg px-2 py-0.5 ml-1">This Month</a>
            @endif
            @endif
        </div>

        <div class="flex items-center gap-2">
            {{-- Week/Month toggle --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
                <a href="{{ meetingNavUrl(['view' => 'week']) }}" class="text-xs px-3 py-1 rounded-md transition-colors {{ $viewMode === 'week' ? 'bg-white text-gray-700 shadow-sm font-medium' : 'text-gray-500 hover:text-gray-700' }}">Week</a>
                <a href="{{ meetingNavUrl(['view' => 'month']) }}" class="text-xs px-3 py-1 rounded-md transition-colors {{ $viewMode === 'month' ? 'bg-white text-gray-700 shadow-sm font-medium' : 'text-gray-500 hover:text-gray-700' }}">Month</a>
            </div>
            <a href="{{ route('meetings.create') }}"
               class="flex items-center gap-1.5 bg-indigo-600 text-white text-xs font-medium px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New Meeting
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif

    {{-- Week View --}}
    @if($viewMode === 'week')
    <div class="overflow-x-auto pb-2">
        <div class="flex gap-2" style="min-width:max-content">
            @foreach($weekDays as $day)
            @php
                $isToday = $day['date']->isToday();
                $isPast  = $day['date']->isPast() && !$isToday;
            @endphp
            <div class="rounded-xl border {{ $isToday ? 'border-indigo-300 shadow-sm' : 'border-gray-200' }} bg-white flex flex-col min-h-[160px]" style="width:180px;flex-shrink:0">
                <div class="px-2.5 py-2 border-b {{ $isToday ? 'bg-indigo-600 border-indigo-600' : 'bg-gray-50 border-gray-100' }}">
                    <p class="text-[10px] font-semibold uppercase tracking-wider {{ $isToday ? 'text-indigo-200' : 'text-gray-400' }}">{{ $day['date']->format('D') }}</p>
                    <p class="text-sm font-bold {{ $isToday ? 'text-white' : ($isPast ? 'text-gray-300' : 'text-gray-700') }}">{{ $day['date']->format('d') }}</p>
                </div>
                <div class="flex-1 p-1.5 space-y-1">
                    @forelse($day['meetings'] as $meeting)
                    <a href="{{ route('meetings.show', $meeting) }}"
                       class="block rounded-lg p-1.5 text-xs hover:opacity-90 transition-opacity"
                       style="background:{{ $meeting->room?->color ?? '#6366f1' }}20;border-left:3px solid {{ $meeting->room?->color ?? '#6366f1' }}">
                        <p class="font-semibold text-gray-800 truncate">{{ $meeting->title }}</p>
                        <p class="text-gray-500">{{ $meeting->start_at->format('H:i') }} – {{ $meeting->end_at->format('H:i') }}</p>
                        @if($meeting->room)
                        <p class="text-gray-400 truncate">{{ $meeting->room->name }}</p>
                        @elseif($meeting->is_online)
                        <p class="text-indigo-400">Online</p>
                        @endif
                    </a>
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

    {{-- Month View --}}
    @else
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div style="display:grid;grid-template-columns:repeat(7,1fr);" class="border-b border-gray-100">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
            <div class="py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-400 {{ $loop->index >= 5 ? 'bg-gray-50' : '' }}">{{ $dow }}</div>
            @endforeach
        </div>
        @foreach($monthGrid as $week)
        <div style="display:grid;grid-template-columns:repeat(7,1fr);" class="{{ !$loop->last ? 'border-b border-gray-100' : '' }}">
            @foreach($week as $day)
            @php
                $isToday   = $day['date']->isToday();
                $inMonth   = $day['inMonth'];
                $isWeekend = in_array($day['date']->dayOfWeek, [0, 6]);
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
                @foreach($day['meetings']->take(3) as $meeting)
                <a href="{{ route('meetings.show', $meeting) }}"
                   class="block rounded text-[10px] px-1 py-0.5 mb-0.5 truncate font-medium hover:opacity-80"
                   style="background:{{ $meeting->room?->color ?? '#6366f1' }}25;color:{{ $meeting->room?->color ?? '#6366f1' }}"
                   title="{{ $meeting->title }} {{ $meeting->start_at->format('H:i') }}">
                    {{ $meeting->start_at->format('H:i') }} {{ Str::limit($meeting->title, 16) }}
                </a>
                @endforeach
                @if($day['meetings']->count() > 3)
                <span class="text-[10px] text-gray-400 pl-1">+{{ $day['meetings']->count() - 3 }} more</span>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
