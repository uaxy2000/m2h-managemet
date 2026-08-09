@extends('layouts.app')

@section('title', 'Ödemeler')
@section('heading', 'Ödemeler')

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

<div x-data="{ open: false, userId: '{{ old('user_id') }}' }">

{{-- ── Özet Tablosu ─────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Kullanıcı Özeti</h3>
        <button @click="open = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Ödeme Ekle
        </button>
    </div>
    @if($userStats->isEmpty())
    <div class="px-5 py-8 text-center text-sm text-gray-400">Henüz kayıtlı lead yok.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3 font-medium">Kullanıcı</th>
                    <th class="text-right px-4 py-3 font-medium">Toplam Register</th>
                    <th class="text-right px-4 py-3 font-medium">Ödendi</th>
                    <th class="text-right px-4 py-3 font-medium">Ödenmedi</th>
                    <th class="text-right px-4 py-3 font-medium">Son Birim Fiyat</th>
                    <th class="text-right px-5 py-3 font-medium">Tahmini Alacak</th>
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

{{-- ── Ödeme Geçmişi ─────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ödeme Geçmişi</h3>
    </div>
    @if($payments->isEmpty())
    <div class="px-5 py-10 text-center text-sm text-gray-400">Henüz ödeme kaydı yok.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="text-left px-5 py-3 font-medium">Tarih</th>
                    <th class="text-left px-4 py-3 font-medium">Kullanıcı</th>
                    <th class="text-right px-4 py-3 font-medium">Tutar</th>
                    <th class="text-right px-4 py-3 font-medium">Lead</th>
                    <th class="text-right px-4 py-3 font-medium">Birim Fiyat</th>
                    <th class="text-left px-4 py-3 font-medium">Not</th>
                    <th class="text-left px-4 py-3 font-medium">Kaydeden</th>
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
                    <td class="px-5 py-3 text-right">
                        <form method="POST" action="{{ route('payments.destroy', $p) }}"
                              onsubmit="return confirm('Bu ödeme kaydını silmek istediğinizden emin misiniz?\nBağlı leadler tekrar ödenmemiş sayılacak.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-gray-300 hover:text-red-500 transition-colors">Sil</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Ödeme Ekle Modal ──────────────────────────────────────────────────── --}}
<div x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="open = false">
    <div class="absolute inset-0 bg-black/30" @click="open = false"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.stop>

        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-gray-800">Ödeme Ekle</h2>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
            @csrf

            {{-- Kullanıcı --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kullanıcı</label>
                <select name="user_id" x-model="userId" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Seçin —</option>
                    @foreach($internalUsers as $u)
                    <option value="{{ $u->id }}" {{ old('user_id') === $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Tutar + Para Birimi --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tutar</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           value="{{ old('amount') }}"
                           placeholder="0.00"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Para Birimi</label>
                    <select name="currency" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="TRY" {{ old('currency', 'TRY') === 'TRY' ? 'selected' : '' }}>TRY</option>
                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                    </select>
                </div>
            </div>

            {{-- Lead Sayısı + Tarih --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kaç Lead İçin</label>
                    <input type="number" name="lead_count" min="1" required
                           value="{{ old('lead_count') }}"
                           placeholder="100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ödeme Tarihi</label>
                    <input type="date" name="paid_at" required
                           value="{{ old('paid_at', date('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{-- Not --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Not <span class="text-gray-400">(opsiyonel)</span></label>
                <input type="text" name="note" maxlength="500"
                       value="{{ old('note') }}"
                       placeholder="Ek açıklama..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex justify-end gap-3 pt-1">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">İptal</button>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- x-data --}}

@if(old('user_id'))
<script>document.addEventListener('DOMContentLoaded', () => { /* reopen modal on validation error */ });</script>
@endif

@endsection
