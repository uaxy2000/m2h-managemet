@extends('layouts.app')
@section('title', $meeting->title)
@section('heading', $meeting->title)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-5">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif

    {{-- Main card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">{{ $meeting->title }}</h2>
                @if($meeting->description)
                <p class="text-sm text-gray-500 mt-1">{{ $meeting->description }}</p>
                @endif
            </div>
            @if(auth()->user()->isInternalAdmin())
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('meetings.edit', $meeting) }}"
                   class="text-xs border border-gray-300 text-gray-600 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors">Edit</a>
                <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" onsubmit="return confirm('Delete this meeting?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs border border-red-200 text-red-600 rounded-lg px-3 py-1.5 hover:bg-red-50 transition-colors">Delete</button>
                </form>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Start</p>
                <p class="text-sm font-medium text-gray-800">{{ $meeting->start_at->format('D, d M Y · H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">End</p>
                <p class="text-sm font-medium text-gray-800">{{ $meeting->end_at->format('D, d M Y · H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Duration</p>
                <p class="text-sm font-medium text-gray-800">{{ $meeting->start_at->diffInMinutes($meeting->end_at) }} min</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Location</p>
                @if($meeting->room)
                <p class="text-sm font-medium text-gray-800">
                    <span class="inline-block w-2.5 h-2.5 rounded-full mr-1" style="background:{{ $meeting->room->color }}"></span>
                    {{ $meeting->room->name }}
                    @if($meeting->room->location) <span class="text-gray-400">· {{ $meeting->room->location }}</span> @endif
                </p>
                @elseif($meeting->is_online)
                <div>
                    <p class="text-sm font-medium text-indigo-600">Online · Google Meet</p>
                    @if($meeting->online_url)
                    <a href="{{ $meeting->online_url }}" target="_blank" class="text-xs text-indigo-500 hover:underline">Join meeting →</a>
                    @endif
                </div>
                @else
                <p class="text-sm text-gray-400">—</p>
                @endif
            </div>
        </div>

        <div class="pt-2 border-t border-gray-100">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Created by</p>
            <p class="text-sm text-gray-700">{{ $meeting->creator?->name ?? '—' }}</p>
        </div>
    </div>

    {{-- Participants --}}
    @if($meeting->participants->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Participants ({{ $meeting->participants->count() }})</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($meeting->participants as $p)
            <div class="flex items-center gap-3 px-5 py-2.5">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0
                    {{ $p->participant_type === 'internal_user' ? 'bg-indigo-500' : ($p->participant_type === 'lead' ? 'bg-emerald-500' : 'bg-amber-500') }}">
                    {{ strtoupper(substr($p->participant_name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $p->participant_name }}</p>
                    @if($p->participant_email)
                    <p class="text-xs text-gray-400">{{ $p->participant_email }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-medium uppercase tracking-wide
                        {{ $p->participant_type === 'internal_user' ? 'text-indigo-500' : ($p->participant_type === 'lead' ? 'text-emerald-600' : 'text-amber-600') }}">
                        {{ str_replace('_', ' ', $p->participant_type) }}
                    </span>
                    @if($p->google_event_id)
                    <span title="Synced to Google Calendar" class="text-green-400">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <a href="{{ route('meetings.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-gray-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
        Back to Meetings
    </a>
</div>
@endsection
