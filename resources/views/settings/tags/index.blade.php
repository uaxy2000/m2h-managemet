@extends('layouts.app')

@section('title', 'Tags')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-3 gap-6">

    {{-- Left: Groups management --}}
    <div class="col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Tag Groups</h3>

            {{-- Add group form --}}
            <form method="POST" action="{{ route('settings.tag-groups.store') }}" class="flex gap-2 mb-4">
                @csrf
                <input type="text" name="name" placeholder="Group name…" required maxlength="50"
                       class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit"
                        class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                    Add
                </button>
            </form>
            @error('name')<p class="text-red-500 text-xs mb-3">{{ $message }}</p>@enderror

            @if($groups->isEmpty())
            <p class="text-sm text-gray-400">No groups yet.</p>
            @else
            <ul class="divide-y divide-gray-100">
                @foreach($groups as $group)
                <li x-data="{ editing: false, name: '{{ addslashes($group->name) }}' }" class="py-2.5">
                    {{-- View mode --}}
                    <div x-show="!editing" class="flex items-center gap-3">
                        <span class="text-sm text-gray-800 flex-1" x-text="name"></span>
                        <span class="text-xs text-gray-400">{{ $group->tags->count() }}</span>
                        <button type="button" @click="editing = true"
                                class="p-1 text-gray-400 hover:text-indigo-500 rounded transition-colors" title="Rename">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                            </svg>
                        </button>
                        <form method="POST" action="{{ route('settings.tag-groups.destroy', $group) }}"
                              onsubmit="return confirm('Delete group \'{{ addslashes($group->name) }}\'? Tags will become ungrouped.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1 text-gray-400 hover:text-red-500 rounded transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    {{-- Edit mode --}}
                    <form x-show="editing"
                          method="POST" action="{{ route('settings.tag-groups.update', $group) }}"
                          class="flex gap-2" @submit="name = $el.querySelector('input').value">
                        @csrf @method('PUT')
                        <input type="text" name="name" :value="name" required maxlength="50" x-ref="input"
                               x-init="$watch('editing', v => { if (v) $nextTick(() => $refs.input.focus()) })"
                               class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit"
                                class="px-3 py-1 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                            Save
                        </button>
                        <button type="button" @click="editing = false"
                                class="px-3 py-1 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- Right: Tags list grouped --}}
    <div class="col-span-2">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Labels for categorising and filtering leads.</p>
            <a href="{{ route('settings.tags.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Tag
            </a>
        </div>

        @if($groups->isEmpty() && $ungrouped->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
            <p class="text-gray-400 text-sm">No tags yet.</p>
            <a href="{{ route('settings.tags.create') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-2 inline-block">
                Create your first tag →
            </a>
        </div>
        @else

        {{-- Grouped tags --}}
        @foreach($groups as $group)
        @if($group->tags->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 mb-4">
            <div class="px-5 py-3 border-b border-gray-100">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $group->name }}</span>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($group->tags as $tag)
                @include('settings.tags._row', ['tag' => $tag])
                @endforeach
            </div>
        </div>
        @endif
        @endforeach

        {{-- Ungrouped tags --}}
        @if($ungrouped->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200">
            @if($groups->isNotEmpty())
            <div class="px-5 py-3 border-b border-gray-100">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Ungrouped</span>
            </div>
            @endif
            <div class="divide-y divide-gray-100">
                @foreach($ungrouped as $tag)
                @include('settings.tags._row', ['tag' => $tag])
                @endforeach
            </div>
        </div>
        @endif

        @endif
    </div>

</div>
@endsection
