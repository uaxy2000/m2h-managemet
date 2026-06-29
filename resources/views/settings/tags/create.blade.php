@extends('layouts.app')

@section('title', 'New Tag')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="mb-5">
    <a href="{{ route('settings.tags.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Tags
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-6 max-w-sm"
     x-data="{ color: '{{ old('color', '#6366f1') }}' }">

    <h2 class="text-sm font-semibold text-gray-700 mb-6">New Tag</h2>

    <form method="POST" action="{{ route('settings.tags.store') }}">
        @csrf

        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   placeholder="e.g. Hot Lead"
                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 {{ $errors->has('name') ? 'border-red-400' : '' }}">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
            <input type="hidden" name="color" :value="color">
            <div class="flex flex-wrap gap-2">
                @foreach(\App\Models\Tag::COLORS as $c)
                <button type="button" @click="color = '{{ $c }}'"
                        class="w-7 h-7 rounded-full border-2 transition-all"
                        :class="color === '{{ $c }}' ? 'border-gray-800 scale-110' : 'border-transparent hover:scale-105'"
                        style="background-color: {{ $c }}"></button>
                @endforeach
            </div>
            <div class="flex items-center gap-2 mt-3">
                <div class="w-5 h-5 rounded-full border border-gray-200" :style="'background-color:' + color"></div>
                <span class="text-xs text-gray-500 font-mono" x-text="color"></span>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
            <a href="{{ route('settings.tags.index') }}"
               class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg transition-colors font-medium">
                Create Tag
            </button>
        </div>
    </form>
</div>
@endsection
