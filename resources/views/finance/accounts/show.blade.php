@extends('layouts.app')
@section('title', ($account->user?->name ?? $account->company?->name ?? $account->name) . ' — Ledger')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('finance.accounts.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $account->user?->name ?? $account->company?->name ?? $account->name }}
            </h1>
            <p class="text-xs text-gray-400 mt-0.5">{{ $account->typeLabel() }} · {{ $account->currency }}</p>
        </div>
    </div>

    {{-- Balance card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Current Balance</p>
            <p class="text-3xl font-bold mt-1 {{ $balance >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                {{ number_format($balance, 2) }} {{ $account->currency }}
            </p>
        </div>
        @if(in_array($account->type, ['current_person','current_company']))
        <div class="text-right text-xs text-gray-400">
            @if($account->type === 'current_person')
                <p>Negative = company owes this person (unreimbursed expenses)</p>
            @else
                <p>Positive = this company owes us</p>
            @endif
        </div>
        @endif
    </div>

    {{-- Movements --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Movement History</h2>
        </div>
        @if($movements->isEmpty())
        <p class="text-sm text-gray-400 text-center py-8">No movements yet.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Running</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $r = 0; $runningMap = []; foreach($movements as $m) { $r += $m->amount; $runningMap[$m->id] = $r; } @endphp
                    @foreach($movements as $mv)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $mv->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $mv->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-400">
                            {{ class_basename($mv->movable_type ?? '') ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-semibold whitespace-nowrap
                            {{ $mv->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $mv->amount >= 0 ? '+' : '' }}{{ number_format($mv->amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-gray-600 whitespace-nowrap">
                            {{ isset($runningMap[$mv->id]) ? number_format($runningMap[$mv->id], 2) : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
