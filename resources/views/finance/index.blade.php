@extends('layouts.app')
@section('title', 'Finance')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Finance Overview</h1>
        <div class="flex gap-2">
            <a href="{{ route('finance.accounts.index') }}" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-200 transition-colors font-medium">Accounts</a>
            <a href="{{ route('finance.expenses.index') }}" class="px-3 py-1.5 text-sm bg-red-50 text-red-700 rounded-lg border border-red-200 hover:bg-red-100 transition-colors font-medium">Expenses</a>
            <a href="{{ route('finance.incomes.index') }}" class="px-3 py-1.5 text-sm bg-green-50 text-green-700 rounded-lg border border-green-200 hover:bg-green-100 transition-colors font-medium">Incomes</a>
            <a href="{{ route('finance.transfers.index') }}" class="px-3 py-1.5 text-sm bg-indigo-50 text-indigo-700 rounded-lg border border-indigo-200 hover:bg-indigo-100 transition-colors font-medium">Transfers</a>
            <a href="{{ route('finance.meta-billing') }}" class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors font-medium">Meta Billing</a>
        </div>
    </div>

    {{-- This month summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide">This Month Income</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($monthIncomes, 2) }} ₺</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide">This Month Expense</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($monthExpenses, 2) }} ₺</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide">Net (Month, TRY)</p>
            @php $net = $monthIncomes - $monthExpenses; @endphp
            <p class="text-2xl font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">{{ number_format($net, 2) }} ₺</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide">Bank + Cash (TRY)</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalBankCash, 2) }} ₺</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Account balances --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Banks --}}
            @if($banks->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Banks</h2>
                <div class="space-y-2">
                    @foreach($banks as $acc)
                    <div class="flex items-center justify-between">
                        <a href="{{ route('finance.accounts.show', $acc) }}" class="text-sm text-gray-700 hover:text-indigo-600">{{ $acc->name }}</a>
                        <span class="text-sm font-semibold font-mono {{ $acc->current_balance >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                            {{ number_format($acc->current_balance, 2) }} {{ $acc->currency }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Cashes --}}
            @if($cashes->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Cash</h2>
                <div class="space-y-2">
                    @foreach($cashes as $acc)
                    <div class="flex items-center justify-between">
                        <a href="{{ route('finance.accounts.show', $acc) }}" class="text-sm text-gray-700 hover:text-indigo-600">{{ $acc->name }}</a>
                        <span class="text-sm font-semibold font-mono {{ $acc->current_balance >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                            {{ number_format($acc->current_balance, 2) }} {{ $acc->currency }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Person Currents --}}
            @if($persons->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Person Accounts</h2>
                <div class="space-y-2">
                    @foreach($persons as $acc)
                    <div class="flex items-center justify-between">
                        <a href="{{ route('finance.accounts.show', $acc) }}" class="text-sm text-gray-700 hover:text-indigo-600">
                            {{ $acc->user?->name ?? $acc->name }}
                        </a>
                        <span class="text-sm font-semibold font-mono {{ $acc->current_balance > 0 ? 'text-orange-600' : 'text-gray-800' }}">
                            {{ number_format($acc->current_balance, 2) }} {{ $acc->currency }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Company Currents --}}
            @if($companies->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Company Accounts</h2>
                <div class="space-y-2">
                    @foreach($companies as $acc)
                    <div class="flex items-center justify-between">
                        <a href="{{ route('finance.accounts.show', $acc) }}" class="text-sm text-gray-700 hover:text-indigo-600">
                            {{ $acc->company?->name ?? $acc->name }}
                        </a>
                        <span class="text-sm font-semibold font-mono {{ $acc->current_balance > 0 ? 'text-green-600' : 'text-gray-800' }}">
                            {{ number_format($acc->current_balance, 2) }} {{ $acc->currency }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($banks->isEmpty() && $cashes->isEmpty() && $persons->isEmpty() && $companies->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                <p class="text-sm text-gray-400">No accounts yet.</p>
                <a href="{{ route('finance.accounts.index') }}" class="mt-2 inline-block text-xs text-indigo-600 hover:underline">Set up accounts →</a>
            </div>
            @endif
        </div>

        {{-- Right column: activity + expense breakdown --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Expense breakdown this month --}}
            @if($expenseByCategory->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Expenses This Month by Category</h2>
                <div class="space-y-2">
                    @foreach($expenseByCategory as $row)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">{{ $row->category_name }}</span>
                        <span class="text-sm font-mono font-semibold text-red-600">{{ number_format($row->total, 2) }} ₺</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Recent Incomes --}}
            @if($recentIncomes->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recent Incomes</h2>
                    <a href="{{ route('finance.incomes.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
                </div>
                <div class="space-y-2">
                    @foreach($recentIncomes as $inc)
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800 truncate">{{ $inc->description ?? $inc->category?->name }}</p>
                            <p class="text-xs text-gray-400">{{ $inc->date->format('d M Y') }} @if($inc->company) · {{ $inc->company->name }} @endif</p>
                        </div>
                        <span class="text-sm font-mono font-semibold text-green-600 flex-shrink-0">+{{ number_format($inc->amount, 2) }} {{ $inc->currency }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Recent Expenses --}}
            @if($recentExpenses->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recent Expenses</h2>
                    <a href="{{ route('finance.expenses.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
                </div>
                <div class="space-y-2">
                    @foreach($recentExpenses as $exp)
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800 truncate">{{ $exp->description ?? $exp->category?->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $exp->date->format('d M Y') }}
                                @if($exp->paidBy) · {{ $exp->paidBy->name }} @endif
                                · {{ $exp->category?->name }}
                            </p>
                        </div>
                        <span class="text-sm font-mono font-semibold text-red-600 flex-shrink-0">-{{ number_format($exp->amount, 2) }} {{ $exp->currency }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
