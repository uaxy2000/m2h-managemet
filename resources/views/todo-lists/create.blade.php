@extends('layouts.app')
@section('title', 'New ToDo List')
@section('heading', 'New ToDo List')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <form method="POST" action="{{ route('todo-lists.store') }}" id="todo-create-form">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">

            {{-- Basic info --}}
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required autofocus
                           class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g. Office supplies, Q3 goals…">
                    @error('title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea name="description" rows="2"
                              class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Short context…">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Members --}}
            <div class="p-5" x-data="memberPicker()">
                <p class="text-xs font-semibold text-gray-600 mb-3">Members <span class="text-gray-400 font-normal">(who can view &amp; add items)</span></p>
                <div class="relative mb-3">
                    <input type="text" x-model="search" placeholder="Search users…"
                           class="w-full rounded-lg border-gray-200 text-sm pl-8 focus:ring-indigo-500 focus:border-indigo-500">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                <div class="space-y-1 max-h-48 overflow-y-auto">
                    @foreach($allUsers as $u)
                    <label x-show="'{{ strtolower($u->name) }}'.includes(search.toLowerCase())"
                           class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="members[]" value="{{ $u->id }}"
                               @checked(in_array($u->id, old('members', [])))
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">{{ $u->name }}</span>
                        @if($u->company)
                        <span class="text-xs text-gray-400">{{ $u->company->name }}</span>
                        @endif
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Linked boards --}}
            <div class="p-5">
                <p class="text-xs font-semibold text-gray-600 mb-3">Link to boards <span class="text-gray-400 font-normal">(optional)</span></p>
                @if($allBoards->isEmpty())
                <p class="text-xs text-gray-400">No boards yet.</p>
                @else
                <div class="space-y-1 max-h-40 overflow-y-auto">
                    @foreach($allBoards as $board)
                    <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="boards[]" value="{{ $board->id }}"
                               @checked(in_array($board->id, old('boards', [])))
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">{{ $board->title }}</span>
                    </label>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('todo-lists.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Create list
            </button>
        </div>
    </form>
</div>

<script>
function memberPicker() {
    return { search: '' };
}
</script>
@endsection
