@extends('layouts.app')

@section('title', 'New Pipeline')
@section('heading', 'Pipeline Configuration')

@section('content')
<div class="mb-6">
    <a href="{{ route('settings.pipelines.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Pipelines
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-6 max-w-lg">
    <h2 class="text-sm font-semibold text-gray-700 mb-5">New Pipeline</h2>

    <form method="POST" action="{{ route('settings.pipelines.store') }}">
        @csrf

        <div class="mb-6">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                placeholder="e.g. Main Pipeline"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            >
            @error('name')
                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('settings.pipelines.index') }}"
               class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                Create Pipeline
            </button>
        </div>
    </form>
</div>
@endsection
