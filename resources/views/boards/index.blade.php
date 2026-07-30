@extends('layouts.app')

@section('title', 'Boards')
@section('heading', 'Boards')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">{{ $boards->count() }} board{{ $boards->count() !== 1 ? 's' : '' }}</p>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('boards.create') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Board
        </a>
        @endif
    </div>

    @if($boards->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
        </svg>
        <p class="text-sm">No boards yet.</p>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($boards as $board)
        @php $hasUnread = $board->hasUnreadFor($user); @endphp
        <a href="{{ route('boards.show', $board) }}"
           class="block bg-white rounded-xl border {{ $hasUnread ? 'border-indigo-300 shadow-md shadow-indigo-50' : 'border-gray-200 hover:border-gray-300' }} p-5 transition-all hover:shadow-sm group">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors leading-snug">
                    {{ $board->title }}
                </h2>
                @if($hasUnread)
                <span class="flex-shrink-0 w-2.5 h-2.5 bg-indigo-500 rounded-full mt-1.5"></span>
                @endif
            </div>

            @if($board->description)
            <p class="mt-1.5 text-sm text-gray-500 line-clamp-2">{{ $board->description }}</p>
            @endif

            <div class="mt-4 flex items-center gap-4 text-xs text-gray-400">
                <span>{{ $board->cards->count() }} card{{ $board->cards->count() !== 1 ? 's' : '' }}</span>
                <span>·</span>
                <span>{{ $board->updated_at->diffForHumans() }}</span>
            </div>

            @if($board->permissions->isNotEmpty())
            <p class="mt-2 text-xs text-gray-400 truncate" title="{{ $board->permissionSummary() }}">
                <svg class="w-3 h-3 inline mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
                {{ $board->permissionSummary() }}
            </p>
            @endif
        </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
