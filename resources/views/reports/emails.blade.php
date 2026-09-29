@extends('layouts.app')
@section('title', 'Email Activity')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Email Activity</h1>
            <p class="text-sm text-gray-500 mt-0.5">IMAP-synced emails matched to leads</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-xl p-4 mb-5 flex flex-wrap gap-3 items-end">

        {{-- Date range --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- Account multi-select --}}
        @if($isAdmin)
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">Account</label>
            <div class="flex flex-wrap gap-1.5 border border-gray-300 rounded-lg px-3 py-1.5 bg-white min-w-[200px]">
                @if($sharedAccount)
                <label class="flex items-center gap-1 text-sm cursor-pointer">
                    <input type="checkbox" name="accounts[]" value="{{ $sharedAccount }}"
                           {{ in_array($sharedAccount, $selectedAccounts) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    <span class="text-gray-700">{{ $sharedAccount }}</span>
                </label>
                @endif
                @foreach($userAccounts as $u)
                <label class="flex items-center gap-1 text-sm cursor-pointer">
                    <input type="checkbox" name="accounts[]" value="{{ $u->imap_user }}"
                           {{ in_array($u->imap_user, $selectedAccounts) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    <span class="text-gray-700">{{ $u->name }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Lead search --}}
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">Lead</label>
            <input type="text" name="lead" value="{{ $leadSearch }}" placeholder="Name or email…"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-1.5 rounded-lg hover:bg-blue-700 self-end">
            Filter
        </button>
        <a href="{{ route('reports.emails') }}" class="text-sm text-gray-500 hover:text-gray-700 self-end py-1.5">Reset</a>
    </form>

    {{-- Summary --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $totalIn + $totalOut }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Total Emails</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-cyan-600">{{ $totalIn }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Received</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $totalOut }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Sent</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        @if($emails->isEmpty())
        <div class="py-16 text-center text-gray-400 text-sm">No emails found for the selected filters.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">Direction</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Lead</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">From</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">To</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide w-36">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($emails as $email)
                @php
                    $meta    = $email->meta ?? [];
                    $isIn    = $email->type === 'email_in';
                    $lead    = $email->lead;
                    $hasAtts = !empty($meta['attachments']);
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $isIn ? 'text-cyan-600' : 'text-blue-600' }}">
                            {{ $isIn ? '↓ Received' : '↑ Sent' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($lead)
                        <a href="{{ route('leads.show', $lead) }}" class="font-medium text-gray-800 hover:text-blue-600">{{ $lead->name }}</a>
                        <p class="text-xs text-gray-400">{{ $lead->email }}</p>
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-gray-700">{{ $email->description }}</span>
                        @if($hasAtts)
                        <span class="ml-1 text-xs text-gray-400" title="{{ collect($meta['attachments'])->pluck('name')->join(', ') }}">
                            📎 {{ count($meta['attachments']) }}
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $meta['from'] ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ is_array($meta['to'] ?? null) ? implode(', ', $meta['to']) : ($meta['to'] ?? '—') }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                        {{ $email->created_at->format('d M Y · H:i') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($emails->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $emails->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
