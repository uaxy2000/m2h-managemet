@extends('layouts.app')
@section('title', 'Meeting Rooms')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="max-w-3xl space-y-5">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Room list --}}
    @if($rooms->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rooms ({{ $rooms->count() }})</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($rooms as $room)
            <div x-data="{ editing: false }" class="px-5 py-3">

                {{-- View row --}}
                <div x-show="!editing" class="flex items-center gap-4">
                    <span class="w-4 h-4 rounded-full flex-shrink-0 border border-gray-200" style="background:{{ $room->color }}"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $room->name }}</p>
                        <p class="text-xs text-gray-400">
                            @if($room->capacity) {{ $room->capacity }} people @endif
                            @if($room->capacity && $room->location) · @endif
                            @if($room->location) {{ $room->location }} @endif
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full font-medium {{ $room->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                        {{ $room->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <button @click="editing = true" type="button"
                            class="text-xs text-gray-500 hover:text-gray-800 border border-gray-200 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('settings.meeting-rooms.destroy', $room) }}"
                          onsubmit="return confirm('Delete \'{{ addslashes($room->name) }}\'?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                        </button>
                    </form>
                </div>

                {{-- Edit row --}}
                <form x-show="!false" x-cloak method="POST" action="{{ route('settings.meeting-rooms.update', $room) }}" x-show="editing">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Name *</label>
                            <input name="name" value="{{ old('name', $room->name) }}" required
                                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Color</label>
                            <input name="color" type="color" value="{{ old('color', $room->color) }}" required
                                   class="w-full h-9 border border-gray-300 rounded-lg px-1 py-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Capacity</label>
                            <input name="capacity" type="number" min="1" value="{{ old('capacity', $room->capacity) }}"
                                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-400"
                                   placeholder="e.g. 8">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Location</label>
                            <input name="location" value="{{ old('location', $room->location) }}"
                                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-400"
                                   placeholder="e.g. Floor 2">
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3">
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                            <input name="is_active" type="checkbox" value="1" {{ $room->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-gray-300 text-indigo-600">
                            Active
                        </label>
                        <div class="flex-1"></div>
                        <button type="button" @click="editing = false"
                                class="text-sm text-gray-500 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg transition-colors">
                            Save
                        </button>
                    </div>
                </form>

            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Add new room --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5" x-data="{ open: {{ $rooms->isEmpty() ? 'true' : 'false' }} }">
        <button @click="open = !open" type="button"
                class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Meeting Room
        </button>

        <form x-show="open" x-cloak method="POST" action="{{ route('settings.meeting-rooms.store') }}" class="mt-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Name *</label>
                    <input name="name" value="{{ old('name') }}" required autofocus
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-400"
                           placeholder="e.g. Conference Room A">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Color</label>
                    <input name="color" type="color" value="{{ old('color', '#6366f1') }}" required
                           class="w-full h-9 border border-gray-300 rounded-lg px-1 py-1 cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Capacity</label>
                    <input name="capacity" type="number" min="1" value="{{ old('capacity') }}"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-400"
                           placeholder="e.g. 8">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Location</label>
                    <input name="location" value="{{ old('location') }}"
                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-indigo-400"
                           placeholder="e.g. Floor 2">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-3">
                <button type="button" @click="open = false"
                        class="text-sm text-gray-500 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg transition-colors">
                    Create Room
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
