@extends('layouts.app')
@section('title', 'Meta Billing')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6"
     x-data="{
         modal: false,
         tx: {},
         openImport(tx) { this.tx = tx; this.modal = true; }
     }">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('finance.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">Meta Billing</h1>
        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ $currency }}</span>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
    @endif

    @if($fetchError)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
        <strong>API Error:</strong> {{ $fetchError }}
    </div>
    @endif

    {{-- Transactions table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Recent Billing Transactions</h2>
            <span class="text-xs text-gray-400">{{ count($transactions) }} transactions</span>
        </div>

        @if(empty($transactions))
        <p class="text-sm text-gray-400 text-center py-10">
            @if($fetchError || !app(\App\Services\MetaAdsService::class)->isConfigured())
                Meta Ads API not configured.
            @else
                No billing transactions found.
            @endif
        </p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Period</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Method</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transactions as $tx)
                    @php $imported = isset($importedIds[$tx['id']]); @endphp
                    <tr class="hover:bg-gray-50 {{ $imported ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap text-xs">
                            {{ $tx['date_start'] }} – {{ $tx['date_stop'] }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-gray-800 whitespace-nowrap">
                            {{ number_format($tx['amount'], 2) }} {{ $currency }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($tx['status'] === 'SUCCESS')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Success</span>
                            @elseif($tx['status'] === 'FAILED')
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Failed</span>
                            @elseif($tx['status'] === 'REFUND')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Refund</span>
                            @else
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $tx['status'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $tx['payment_option'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($imported)
                            <span class="text-xs text-gray-400">Imported</span>
                            @elseif($tx['status'] === 'SUCCESS' || $tx['status'] === 'ADJUSTMENT')
                            <button type="button"
                                    @click="openImport({{ Js::from($tx) }})"
                                    class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg transition-colors">
                                Import
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Import Modal --}}
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="modal = false">
        <div class="absolute inset-0 bg-black/30" @click="modal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>

            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-semibold text-gray-800">Import to Expenses</h2>
                <button @click="modal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('finance.meta-billing.import') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="transaction_id" :value="tx.id">

                <div class="bg-gray-50 rounded-lg px-4 py-3 text-sm">
                    <p class="text-gray-500 text-xs mb-1">Transaction</p>
                    <p class="font-medium text-gray-800" x-text="tx.date_start + ' – ' + tx.date_stop"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Date *</label>
                        <input type="date" name="date" :value="tx.date_stop" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Currency *</label>
                        <select name="currency" class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach(['USD','EUR','TRY','GBP'] as $c)
                            <option value="{{ $c }}" {{ $c === $currency ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Amount *</label>
                    <input type="number" name="amount" :value="tx.amount" step="0.01" min="0.01" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div x-data="{ groupId: '', get types() { return [] } }">
                    <label class="block text-xs text-gray-500 mb-1">Expense Category *</label>
                    <select name="category_id" required
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select —</option>
                        @foreach($categories as $group)
                        <optgroup label="{{ $group->name }}">
                            @foreach($group->children as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Source Account <span class="text-gray-400">(bank / cash / card)</span></label>
                    <select name="source_account_id"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— None —</option>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">
                            {{ $acc->name }} ({{ $acc->currency }})
                            @if($acc->card_last6) ···{{ $acc->card_last6 }}@endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" @click="modal = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Import to Expenses
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
