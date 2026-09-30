@extends('layouts.app')

@section('title', 'Programs')
@section('heading', 'Settings')

@section('content')
@include('settings._nav_crm')

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage residency and investment programs offered to leads.</p>
    <a href="{{ route('settings.programs.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Program
    </a>
</div>

@foreach(['success','error'] as $key)
@if(session($key))
<div class="{{ $key === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' }} border rounded-lg px-4 py-3 mb-5 text-sm">
    {{ session($key) }}
</div>
@endif
@endforeach

@if($programs->isEmpty())
<div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
    <p class="text-gray-400 text-sm">No programs yet.</p>
    <a href="{{ route('settings.programs.create') }}"
       class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-2 inline-block">
        Create your first program →
    </a>
</div>
@else

@php $grouped = $programs->groupBy('country'); @endphp

<div class="space-y-6">
    @foreach($grouped as $country => $countryPrograms)
    <div>
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-1">{{ $country }}</h3>
        <div class="space-y-3">
            @foreach($countryPrograms as $program)
            <div x-data="{ showPricing: false, showAddForm: false }"
                 class="bg-white rounded-xl border border-gray-200">

                {{-- Program row --}}
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800">{{ $program->name }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                {{ $program->typeLabel() }}
                            </span>
                            @if($program->pricing->isNotEmpty())
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
                                {{ $program->pricing->count() }} price{{ $program->pricing->count() > 1 ? 's' : '' }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <span class="text-xs font-medium px-2.5 py-1 rounded-full flex-shrink-0
                                 {{ $program->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $program->is_active ? 'Active' : 'Inactive' }}
                    </span>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="showPricing = !showPricing" type="button"
                                class="text-sm text-gray-500 hover:text-indigo-600 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-indigo-50 transition-colors">
                            <span x-text="showPricing ? 'Hide Prices' : 'Prices'"></span>
                        </button>
                        <a href="{{ route('settings.programs.edit', $program) }}"
                           class="text-sm text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('settings.programs.destroy', $program) }}"
                              onsubmit="return confirm('Delete \'{{ addslashes($program->name) }}\'?')" class="contents">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Pricing section --}}
                <div x-show="showPricing" x-cloak class="border-t border-gray-100 px-5 py-4">

                    {{-- Pricing table --}}
                    @if($program->pricing->isEmpty())
                    <p class="text-sm text-gray-400 mb-3">No pricing added yet.</p>
                    @else
                    <div class="overflow-x-auto mb-4">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-gray-400 border-b border-gray-100">
                                    <th class="text-left pb-2 pr-4 font-medium">Service Provider</th>
                                    <th class="text-left pb-2 pr-4 font-medium">Effective From</th>
                                    <th class="text-right pb-2 pr-4 font-medium">Currency</th>
                                    <th class="text-right pb-2 pr-4 font-medium">Client Legal Fees</th>
                                    <th class="text-right pb-2 pr-4 font-medium">Provider Share</th>
                                    <th class="text-right pb-2 pr-4 font-medium">Partner Share</th>
                                    <th class="text-right pb-2 pr-4 font-medium">% Legal</th>
                                    <th class="text-right pb-2 pr-4 font-medium">% Investment</th>
                                    <th class="pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($program->pricing as $price)
                                <tr class="{{ $loop->first ? 'font-medium text-gray-800' : 'text-gray-400' }}">
                                    <td class="py-2 pr-4">
                                        {{ $price->serviceProvider?->name ?? '—' }}
                                        @if($loop->first)
                                        <span class="ml-1 text-[10px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full">current</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4">{{ $price->effective_from->format('d M Y') }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $price->currency }}</td>
                                    <td class="py-2 pr-4 text-right font-variant-numeric tabular-nums">{{ number_format((float)$price->client_legal_fees) }}</td>
                                    <td class="py-2 pr-4 text-right font-variant-numeric tabular-nums">{{ number_format((float)$price->provider_share) }}</td>
                                    <td class="py-2 pr-4 text-right font-variant-numeric tabular-nums text-emerald-600">{{ number_format((float)$price->partner_share) }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $price->commission_pct_legal_fees }}%</td>
                                    <td class="py-2 pr-4 text-right">{{ $price->commission_pct_investment ? $price->commission_pct_investment . '%' : '—' }}</td>
                                    <td class="py-2 text-right">
                                        <form method="POST" action="{{ route('settings.programs.pricing.destroy', [$program, $price]) }}"
                                              onsubmit="return confirm('Delete this pricing row?')" class="contents">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    {{-- Add pricing button / form --}}
                    <button @click="showAddForm = !showAddForm" type="button"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                        <span x-text="showAddForm ? '✕ Cancel' : '+ Add pricing'"></span>
                    </button>

                    <form x-show="showAddForm" x-cloak
                          method="POST" action="{{ route('settings.programs.pricing.store', $program) }}"
                          class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @csrf
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Service Provider</label>
                            <select name="service_provider_id" required
                                    class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select…</option>
                                @foreach($serviceProviders as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Currency</label>
                            <select name="currency" required
                                    class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="EUR">EUR</option>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="TRY">TRY</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Effective From</label>
                            <input type="date" name="effective_from" required value="{{ date('Y-m-d') }}"
                                   class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Client Legal Fees</label>
                            <input type="number" name="client_legal_fees" step="0.01" min="0" required
                                   class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Provider Share</label>
                            <input type="number" name="provider_share" step="0.01" min="0" required
                                   class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Partner Share</label>
                            <input type="number" name="partner_share" step="0.01" min="0" required
                                   class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">% Legal Fees</label>
                            <input type="number" name="commission_pct_legal_fees" step="0.01" min="0" max="100" required
                                   class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">% Investment (optional)</label>
                            <input type="number" name="commission_pct_investment" step="0.01" min="0" max="100"
                                   placeholder="N/A"
                                   class="w-full rounded-lg border-gray-300 text-xs py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="col-span-2 sm:col-span-4 flex justify-end">
                            <button type="submit"
                                    class="px-4 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium">
                                Save Pricing
                            </button>
                        </div>
                    </form>

                </div>{{-- end pricing section --}}

            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
