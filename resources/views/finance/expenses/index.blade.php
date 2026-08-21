@extends('layouts.app')
@section('title', 'Expenses')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">

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
            <h1 class="text-xl font-semibold text-gray-900">Expenses</h1>
        </div>
        <button onclick="var d=document.getElementById('addExpenseDetails'); d.open=!d.open; if(d.open) d.scrollIntoView({behavior:'smooth',block:'nearest'});"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Expense
        </button>
    </div>

    {{-- Add Expense Form --}}
    <details id="addExpenseDetails" class="bg-white rounded-xl border border-gray-200" {{ $errors->any() ? 'open' : '' }}>
        <summary class="hidden"></summary>
        <div class="px-5 pb-5 border-t border-gray-100 pt-4">
        <form method="POST" action="{{ route('finance.expenses.store') }}" enctype="multipart/form-data"
              x-data="{
                  groups: {{ Js::from($categories->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'children' => $g->children->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->all()])->values()->all()) }},
                  groupId: '',
                  categoryId: '',
                  get filteredTypes() {
                      if (!this.groupId) return [];
                      const g = this.groups.find(g => g.id === this.groupId);
                      return g ? g.children : [];
                  }
              }">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date *</label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
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
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Amount *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500" placeholder="0.00">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs text-gray-500 mb-1">Currency</label>
                        <select name="currency" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                            @foreach(['TRY','EUR','USD','GBP'] as $c)
                            <option value="{{ $c }}" {{ old('currency','TRY') == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Paid by (person)</label>
                    <select name="paid_by_user_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                        <option value="">— None (direct payment) —</option>
                        @foreach($internalUsers as $u)
                        <option value="{{ $u->id }}" {{ old('paid_by_user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Source account (bank/cash)</label>
                    <select name="source_account_id" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                        <option value="">— None —</option>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('source_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }} ({{ $acc->currency }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Lead (optional)</label>
                    <input type="text" name="lead_id" value="{{ old('lead_id') }}" placeholder="Lead UUID"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" maxlength="1000"
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500 focus:border-red-500" placeholder="Optional notes">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-xs text-gray-500 mb-1">Receipt / Invoice <span class="text-gray-400">(jpg, jpeg, png, pdf — max 10 MB, optional)</span></label>
                    <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                </div>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Save Expense</button>
            </div>
        </form>
        </div>
    </details>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Category</label>
            <select name="category_id" class="rounded-lg border-gray-300 text-sm">
                <option value="">All categories</option>
                @foreach($categories as $group)
                <optgroup label="{{ $group->name }}">
                    @foreach($group->children as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Currency</label>
            <select name="currency" class="rounded-lg border-gray-300 text-sm">
                <option value="">All</option>
                @foreach(['TRY','EUR','USD','GBP'] as $c)
                <option value="{{ $c }}" {{ request('currency') == $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-3 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-900">Filter</button>
    </form>

    {{-- Totals --}}
    @if($totalsByCurrency->isNotEmpty())
    <div class="flex gap-3 flex-wrap">
        @foreach($totalsByCurrency as $cur => $total)
        <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-2 text-sm">
            <span class="text-red-700 font-semibold">{{ number_format($total, 2) }} {{ $cur }}</span>
            <span class="text-red-400 ml-1">total</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($expenses->isEmpty())
        <p class="text-sm text-gray-400 text-center py-10">No expenses for this period.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Paid by</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($expenses as $exp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $exp->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            @if($exp->category)
                            <div class="flex items-center gap-1 flex-wrap">
                                @if($exp->category->parent)
                                <span class="text-xs text-gray-400">{{ $exp->category->parent->name }}</span>
                                <span class="text-gray-300">/</span>
                                @endif
                                <span class="inline-block bg-red-50 text-red-700 text-xs px-2 py-0.5 rounded-full">{{ $exp->category->name }}</span>
                            </div>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 max-w-xs truncate">{{ $exp->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $exp->paidBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $exp->sourceAccount?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold whitespace-nowrap
                            {{ $exp->status === 'approved' ? 'text-red-600' : 'text-gray-400' }}">
                            -{{ number_format($exp->amount, 2) }} {{ $exp->currency }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($exp->status === 'approved')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Approved</span>
                            @elseif($exp->status === 'pending')
                            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Pending</span>
                            @elseif($exp->status === 'rejected')
                            <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Rejected</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($exp->document_path)
                                <a href="{{ route('finance.document', ['type' => 'expense', 'id' => $exp->id]) }}"
                                   target="_blank" class="text-indigo-400 hover:text-indigo-600" title="View document">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                    </svg>
                                </a>
                                @endif
                                @if($exp->status === 'pending')
                                <form method="POST" action="{{ route('finance.expenses.approve', $exp) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-semibold" title="Approve">✓</button>
                                </form>
                                <form method="POST" action="{{ route('finance.expenses.reject', $exp) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-semibold" title="Reject">✕</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('finance.expenses.destroy', $exp) }}"
                                      onsubmit="return confirm('Delete this expense?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
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
