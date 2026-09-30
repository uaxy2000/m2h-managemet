@extends('layouts.app')

@section('title', 'Pricing Overview')
@section('heading', 'Settings')

@section('content')
@include('settings._nav_crm')

<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('settings.programs.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Programs
        </a>
        <h2 class="text-lg font-semibold text-gray-800">Pricing Overview</h2>
        <p class="text-sm text-gray-500">Latest pricing per program & service provider as of today.</p>
    </div>
</div>

@php
    $grouped = $programs->groupBy('country');
    // Collect all SPs that have any pricing, sorted by name
    $allSps = $programs->flatMap(fn($p) => $p->pricing->map->serviceProvider)
        ->filter()
        ->unique('id')
        ->sortBy('name')
        ->values();
@endphp

@if($programs->isEmpty())
<div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
    <p class="text-gray-400 text-sm">No programs yet.</p>
</div>
@else
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 whitespace-nowrap w-36">Country</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Program</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 whitespace-nowrap">Type</th>
                    @foreach($allSps as $sp)
                    <th colspan="5" class="text-center px-4 py-3 font-medium text-indigo-600 border-l border-gray-200 whitespace-nowrap">
                        {{ $sp->name }}
                    </th>
                    @endforeach
                </tr>
                <tr class="border-b border-gray-200 bg-gray-50">
                    <th class="px-4 pb-2"></th>
                    <th class="px-4 pb-2"></th>
                    <th class="px-4 pb-2"></th>
                    @foreach($allSps as $sp)
                    <th class="text-right px-3 pb-2 font-medium text-gray-400 border-l border-gray-200 whitespace-nowrap">Curr.</th>
                    <th class="text-right px-3 pb-2 font-medium text-gray-400 whitespace-nowrap">Client Fees</th>
                    <th class="text-right px-3 pb-2 font-medium text-gray-400 whitespace-nowrap">Provider Share</th>
                    <th class="text-right px-3 pb-2 font-medium text-emerald-600 whitespace-nowrap">Partner Share</th>
                    <th class="text-right px-3 pb-2 font-medium text-gray-400 whitespace-nowrap">% Legal / Inv.</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($grouped as $country => $countryPrograms)
                @php $countryBg = $loop->index % 2 === 0 ? 'bg-white' : 'bg-slate-50'; @endphp
                @foreach($countryPrograms as $program)
                @php
                    $latestBySp = $program->pricing->groupBy('service_provider_id')->map->first();
                    $isFirstInCountry = $loop->first;
                    $countryRowspan = $countryPrograms->count();
                @endphp
                <tr class="{{ $countryBg }} transition-colors">
                    @if($isFirstInCountry)
                    <td rowspan="{{ $countryRowspan }}"
                        class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider text-[10px] align-top border-r border-gray-100 whitespace-nowrap">
                        {{ $country }}
                    </td>
                    @endif
                    <td class="px-4 py-3 text-gray-800 font-medium">{{ $program->name }}</td>
                    <td class="px-4 py-3 text-gray-400 whitespace-nowrap">
                        <span class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px]">{{ $program->typeLabel() }}</span>
                    </td>
                    @foreach($allSps as $sp)
                    @php $price = $latestBySp[$sp->id] ?? null; @endphp
                    @if($price)
                    <td class="text-right px-3 py-3 border-l border-gray-100 text-gray-500">{{ $price->currency }}</td>
                    <td class="text-right px-3 py-3 tabular-nums text-gray-700">{{ number_format((float)$price->client_legal_fees) }}</td>
                    <td class="text-right px-3 py-3 tabular-nums text-gray-500">{{ number_format((float)$price->provider_share) }}</td>
                    <td class="text-right px-3 py-3 tabular-nums font-semibold text-emerald-600">{{ number_format((float)$price->partner_share) }}</td>
                    <td class="text-right px-3 py-3 text-gray-500 whitespace-nowrap">
                        {{ $price->commission_pct_legal_fees }}%
                        @if($price->commission_pct_investment)
                        / {{ $price->commission_pct_investment }}%
                        @else
                        <span class="text-gray-300">/ —</span>
                        @endif
                    </td>
                    @else
                    <td colspan="5" class="text-center px-3 py-3 border-l border-gray-100 text-gray-300">—</td>
                    @endif
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<p class="text-xs text-gray-400 mt-3 text-right">
    {{ $programs->sum(fn($p) => $p->pricing->groupBy('service_provider_id')->count()) }} active pricing entries
    across {{ $programs->count() }} programs
</p>
@endif

@endsection
