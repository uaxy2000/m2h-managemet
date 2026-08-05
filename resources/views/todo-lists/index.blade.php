@extends('layouts.app')
@section('title', 'ToDo Lists')
@section('heading', 'ToDo Lists')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">{{ $lists->count() }} list{{ $lists->count() !== 1 ? 's' : '' }}</p>
        @if($user->isInternalAdmin())
        <a href="{{ route('todo-lists.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New list
        </a>
        @endif
    </div>

    @if($lists->isEmpty())
    <div class="text-center py-16 text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
        </svg>
        <p class="text-sm font-medium">No ToDo lists yet</p>
        @if($user->isInternalAdmin())
        <a href="{{ route('todo-lists.create') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 inline-block">Create your first list →</a>
        @endif
    </div>
    @else
    <div class="space-y-3">
        @foreach($lists as $list)
        @php
            $done  = $list->doneCount();
            $total = $list->totalCount();
            $pct   = $total > 0 ? round($done / $total * 100) : 0;
        @endphp
        <a href="{{ route('todo-lists.show', $list) }}"
           class="block bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 transition-colors">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800">{{ $list->title }}</h3>
                    @if($list->description)
                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $list->description }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-2">
                        <span class="text-xs text-gray-500">{{ $done }}/{{ $total }} done</span>
                        <span class="text-xs text-gray-400">{{ $list->memberSummary() }}</span>
                        @if($list->boards->isNotEmpty())
                        <span class="text-xs text-indigo-500">
                            {{ $list->boards->count() }} board{{ $list->boards->count() !== 1 ? 's' : '' }}
                        </span>
                        @endif
                    </div>
                </div>
                @if($total > 0)
                <div class="flex-shrink-0 text-right">
                    <span class="text-2xl font-bold {{ $pct === 100 ? 'text-emerald-500' : 'text-gray-700' }}">{{ $pct }}%</span>
                </div>
                @endif
            </div>
            @if($total > 0)
            <div class="mt-3 w-full bg-gray-100 rounded-full h-1">
                <div class="h-1 rounded-full transition-all {{ $pct === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}"
                     style="width: {{ $pct }}%"></div>
            </div>
            @endif
        </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
