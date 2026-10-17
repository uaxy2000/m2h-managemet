@extends('layouts.app')
@section('title', 'Cost Calculator')
@section('heading', 'Cost Calculator')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Cost Calculator</h1>
        <p class="text-sm text-gray-500 mt-0.5">Select a programme to calculate costs for the client's family.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($programs as $program)
            <a href="{{ route('cost-calculator.show', $program['slug']) }}"
               class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                <div class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <span class="fi fi-{{ $program['country_code'] }}" style="border-radius:2px"></span>
                    {{ $program['country'] }}
                </div>
                <h2 class="mt-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-700">{{ $program['name'] }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $program['description'] }}</p>
                <span class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-indigo-600">
                    Open calculator
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
