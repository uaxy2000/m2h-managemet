@extends('layouts.finance')
@section('title', 'My Finance')

@section('finance_content')
@php
$groupsJson = $expenseGroups->map(fn($g) => [
    'id'       => $g->id,
    'name'     => $g->name,
    'children' => $g->children->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray(),
])->values()->toJson();
@endphp

<div class="p-6 max-w-3xl mx-auto space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">My Finance</h1>
    </div>

    {{-- Balance card --}}
    @if($myAccount)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">My Current Account Balance</p>
        <p class="text-3xl font-bold {{ $myBalance >= 0 ? 'text-gray-800' : 'text-red-600' }} mt-1">
            {{ number_format($myBalance, 2) }} {{ $myAccount->currency }}
        </p>
        @if($myBalance > 0)
        <p class="text-xs text-amber-600 mt-2">Company owes you this amount (pending reimbursement)</p>
        @elseif($myBalance == 0)
        <p class="text-xs text-gray-400 mt-2">All settled</p>
        @endif
    </div>
    @else
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 text-sm text-amber-700">
        No current account linked to your profile yet. Contact an admin to set it up.
    </div>
    @endif

    {{-- Submit expense --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5"
         x-data="{
             showForm: {{ $errors->any() ? 'true' : 'false' }},
             groups: {{ $groupsJson }},
             groupId: '',
             categoryId: '',
             get filteredTypes() {
                 if (!this.groupId) return [];
                 const g = this.groups.find(g => g.id === this.groupId);
                 return g ? g.children : [];
             }
         }">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700">Submit an Expense</h2>
            <button @click="showForm = !showForm"
                    class="text-xs text-indigo-600 hover:underline" x-text="showForm ? 'Cancel' : '+ New Expense'"></button>
        </div>

        @if($errors->any())
        <div class="mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div x-show="showForm" x-cloak>
            <form method="POST" action="{{ route('finance.expenses.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Date *</label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">Amount *</label>
                            <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500" placeholder="0.00">
                        </div>
                        <div class="w-24">
                            <label class="block text-xs text-gray-500 mb-1">Currency</label>
                            <select name="currency" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach(['TRY','EUR','USD','GBP'] as $c)
                                <option value="{{ $c }}" {{ old('currency','TRY') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Group *</label>
                        <select x-model="groupId" @change="categoryId = ''" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                            <option value="">— Select group —</option>
                            <template x-for="g in groups" :key="g.id">
                                <option :value="g.id" x-text="g.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Type *</label>
                        <select name="category_id" x-model="categoryId" required
                                :disabled="!groupId || filteredTypes.length === 0"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500 disabled:opacity-50">
                            <option value="">— Select type —</option>
                            <template x-for="t in filteredTypes" :key="t.id">
                                <option :value="t.id" x-text="t.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Description</label>
                        <input type="text" name="description" value="{{ old('description') }}" maxlength="1000"
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500" placeholder="What was this expense for?">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Receipt / Invoice <span class="text-gray-400">(jpg, jpeg, png, pdf — max 10 MB)</span></label>
                        <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf"
                               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700">Submit Expense</button>
                    <p class="text-xs text-gray-400">Your submission will be reviewed by an admin before it's approved.</p>
                </div>
            </form>
        </div>
    </div>

    {{-- My submitted expenses --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">My Submitted Expenses</h2>
        </div>
        @if($myExpenses->isEmpty())
        <p class="text-sm text-gray-400 text-center py-8">No expenses yet.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($myExpenses as $exp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $exp->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            @if($exp->category)
                            <div class="flex items-center gap-1 flex-wrap">
                                @if($exp->category->parent)
                                <span class="text-xs text-gray-400">{{ $exp->category->parent->name }}</span>
                                <span class="text-gray-300">/</span>
                                @endif
                                <span class="text-xs bg-red-50 text-red-700 px-2 py-0.5 rounded-full">{{ $exp->category->name }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $exp->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold whitespace-nowrap
                            {{ $exp->status === 'approved' ? 'text-red-600' : 'text-gray-400' }}">
                            {{ number_format($exp->amount, 2) }} {{ $exp->currency }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($exp->status === 'approved')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Approved</span>
                            @elseif($exp->status === 'pending')
                            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Pending</span>
                            @elseif($exp->status === 'rejected')
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium">Rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($exp->document_path)
                            <a href="{{ route('finance.document', ['type' => 'expense', 'id' => $exp->id]) }}"
                               target="_blank" class="text-indigo-500 hover:text-indigo-700" title="View document">
                                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                            </a>
                            @endif
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
