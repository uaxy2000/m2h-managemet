@extends('layouts.app')

@section('title', 'Payments')
@section('heading', 'Payments')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div x-data="{ open: false }">

{{-- ── User Summary ──────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">User Summary</h3>
        <button @click="open = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Payment
        </button>
    </div>
    @if($userStats->isEmpty())
    <div class="px-5 py-8 text-center text-sm text-gray-400">No registered leads yet.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3 font-medium">User</th>
                    <th class="text-right px-4 py-3 font-medium">Total Registered</th>
                    <th class="text-right px-4 py-3 font-medium">Paid</th>
                    <th class="text-right px-4 py-3 font-medium">Unpaid</th>
                    <th class="text-right px-4 py-3 font-medium">Last Unit Price</th>
                    <th class="text-right px-5 py-3 font-medium">Est. Pending</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($userStats as $s)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($s['user']->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ $s['user']->name }}</p>
                                <p class="text-xs text-gray-400">{{ $s['user']->roleLabel() }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-700 tabular-nums">{{ $s['total'] }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        <span class="text-green-600 font-medium">{{ $s['paid'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums">
                        @if($s['unpaid'] > 0)
                        <span class="text-amber-600 font-medium">{{ $s['unpaid'] }}</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-600">
                        @if($s['unit_price'])
                        {{ number_format($s['unit_price'], 2) }}
                        <span class="text-xs text-gray-400">{{ $s['last_payment']->currency }}</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right tabular-nums">
                        @if($s['estimated_pending'])
                        <span class="font-semibold text-indigo-600">
                            {{ number_format($s['estimated_pending'], 2) }}
                            <span class="text-xs font-normal text-indigo-400">{{ $s['last_payment']->currency }}</span>
                        </span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Payment History ───────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Payment History</h3>
    </div>
    @if($payments->isEmpty())
    <div class="px-5 py-10 text-center text-sm text-gray-400">No payments recorded yet.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3 font-medium">Date</th>
                    <th class="text-left px-4 py-3 font-medium">User</th>
                    <th class="text-right px-4 py-3 font-medium">Amount</th>
                    <th class="text-right px-4 py-3 font-medium">Leads</th>
                    <th class="text-right px-4 py-3 font-medium">Unit Price</th>
                    <th class="text-left px-4 py-3 font-medium">Note</th>
                    <th class="text-left px-4 py-3 font-medium">Recorded By</th>
                    <th class="text-center px-4 py-3 font-medium">Doc</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($payments as $p)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $p->paid_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $p->user->name }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-medium text-gray-800 whitespace-nowrap">
                        {{ number_format($p->amount, 2) }}
                        <span class="text-xs text-gray-400 font-normal">{{ $p->currency }}</span>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ $p->lead_count }}</td>
                    <td class="px-4 py-3 text-right tabular-nums text-gray-500 whitespace-nowrap">
                        {{ number_format($p->unitPrice(), 2) }}
                        <span class="text-xs text-gray-400">{{ $p->currency }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $p->note ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $p->createdBy->name }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($p->document_path)
                        <a href="{{ route('finance.document', ['type' => 'payment', 'id' => $p->id]) }}"
                           target="_blank" class="text-indigo-400 hover:text-indigo-600" title="View document">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                        </a>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('payments.destroy', $p) }}"
                              onsubmit="return confirm('Delete this payment record?\nLinked leads will be marked as unpaid again.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-gray-300 hover:text-red-500 transition-colors">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Add Payment Modal ─────────────────────────────────────────────────── --}}
<div x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="open = false">
    <div class="absolute inset-0 bg-black/30" @click="open = false"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>

        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-gray-800">Add Payment</h2>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">User</label>
                <select name="user_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Select —</option>
                    @foreach($internalUsers as $u)
                    <option value="{{ $u->id }}" {{ old('user_id') === $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           value="{{ old('amount') }}" placeholder="0.00"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Currency</label>
                    <select name="currency" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="TRY" {{ old('currency', 'TRY') === 'TRY' ? 'selected' : '' }}>TRY</option>
                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Lead Count</label>
                    <input type="number" name="lead_count" min="1" required
                           value="{{ old('lead_count') }}" placeholder="100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Payment Date</label>
                    <input type="date" name="paid_at" required
                           value="{{ old('paid_at', date('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Source Account <span class="text-gray-400">(bank / cash)</span></label>
                <select name="source_account_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— None —</option>
                    @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('source_account_id') == $acc->id ? 'selected' : '' }}>
                        {{ $acc->name }} ({{ $acc->currency }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Note <span class="text-gray-400">(optional)</span></label>
                <input type="text" name="note" maxlength="500"
                       value="{{ old('note') }}" placeholder="Additional notes..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dekont / Fiş <span class="text-gray-400">(jpg, jpeg, png, pdf — max 10 MB, optional)</span></label>
                <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf"
                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div class="flex justify-end gap-3 pt-1">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- x-data --}}

@endsection
