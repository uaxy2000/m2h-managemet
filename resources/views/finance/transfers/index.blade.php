@extends('layouts.app')
@section('title', 'Transfers')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6" x-data="{ showForm: false }">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('finance.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Transfers</h1>
        </div>
        <button @click="showForm = !showForm"
                class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            + New Transfer
        </button>
    </div>

    {{-- Add Transfer Form --}}
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">New Transfer</h2>
        <form method="POST" action="{{ route('finance.transfers.store') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date *</label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Amount *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="0.00">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs text-gray-500 mb-1">Currency</label>
                        <select name="currency" class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['TRY','EUR','USD','GBP'] as $c)
                            <option value="{{ $c }}" {{ old('currency','TRY') == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From account *</label>
                    <select name="from_account_id" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Select —</option>
                        @foreach($accounts->groupBy('type') as $type => $group)
                        <optgroup label="{{ match($type) { 'bank' => 'Banks', 'cash' => 'Cash', 'current_person' => 'Person Accounts', 'current_company' => 'Company Accounts', default => $type } }}">
                            @foreach($group as $acc)
                            <option value="{{ $acc->id }}" {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->user?->name ?? $acc->company?->name ?? $acc->name }} ({{ $acc->currency }})
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">To account *</label>
                    <select name="to_account_id" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Select —</option>
                        @foreach($accounts->groupBy('type') as $type => $group)
                        <optgroup label="{{ match($type) { 'bank' => 'Banks', 'cash' => 'Cash', 'current_person' => 'Person Accounts', 'current_company' => 'Company Accounts', default => $type } }}">
                            @foreach($group as $acc)
                            <option value="{{ $acc->id }}" {{ old('to_account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->user?->name ?? $acc->company?->name ?? $acc->name }} ({{ $acc->currency }})
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="1000"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g. Salary — Ali / Phone bill reimbursement / SP payment received">
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">Save Transfer</button>
                <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Date filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <button type="submit" class="px-3 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900">Filter</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($transfers->isEmpty())
        <p class="text-sm text-gray-400 text-center py-10">No transfers for this period.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">From</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">To</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transfers as $tr)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $tr->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $tr->fromAccount->user?->name ?? $tr->fromAccount->company?->name ?? $tr->fromAccount->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $tr->toAccount->user?->name ?? $tr->toAccount->company?->name ?? $tr->toAccount->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $tr->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-indigo-600 whitespace-nowrap">
                            {{ number_format($tr->amount, 2) }} {{ $tr->currency }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('finance.transfers.destroy', $tr) }}"
                                  onsubmit="return confirm('Delete this transfer?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
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
    </div>

</div>
@endsection
