@extends('layouts.app')
@section('title', 'Email IMAP Sync')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-4">{{ session('error') }}</div>
@endif

{{-- Shared Account --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-800">Shared Account (info@)</h2>
            <p class="text-xs text-gray-400 mt-0.5">Emails from/to any lead synced from this inbox</p>
        </div>
        @if($settings->get('imap_last_sync_at'))
        <span class="text-xs text-gray-400">Last sync: {{ \Carbon\Carbon::parse($settings->get('imap_last_sync_at'))->diffForHumans() }}</span>
        @endif
    </div>
    <form method="POST" action="{{ route('settings.email-imap.update') }}" class="space-y-3">
        @csrf @method('PUT')
        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">IMAP Host</label>
                <input type="text" name="imap_host" value="{{ $settings->get('imap_host') }}" placeholder="mail.example.com"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
                <input type="number" name="imap_port" value="{{ $settings->get('imap_port', '993') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Encryption</label>
                <select name="imap_encryption" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    @foreach(['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'None'] as $val => $label)
                    <option value="{{ $val }}" {{ $settings->get('imap_encryption', 'ssl') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                <input type="text" name="imap_username" value="{{ $settings->get('imap_username') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Password <span class="text-gray-400">(blank = keep)</span></label>
                <input type="password" name="imap_password" placeholder="••••••••"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
        </div>
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="imap_enabled" value="1" {{ $settings->get('imap_enabled') === '1' ? 'checked' : '' }} class="rounded">
                Enable sync for this account
            </label>
            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-indigo-700">Save</button>
        </div>
    </form>
</div>

{{-- Sync Token & cron-job.org --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-5">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">Sync Endpoint (cron-job.org)</h2>
    @if($settings->get('imap_sync_token'))
    <div class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 font-mono text-xs text-gray-700 break-all mb-3">
        {{ url('/api/email-sync') }}?token={{ $settings->get('imap_sync_token') }}
    </div>
    @else
    <p class="text-xs text-gray-400 mb-3">Token will be generated when you save the shared account settings.</p>
    @endif
    <div class="flex items-center gap-4">
        <form method="POST" action="{{ route('settings.email-imap.regenerate-token') }}">
            @csrf
            <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 border border-gray-300 px-3 py-1.5 rounded-lg"
                    onclick="return confirm('Regenerate token? The old cron-job.org URL will stop working.')">
                Regenerate Token
            </button>
        </form>
        <form method="POST" action="{{ route('settings.email-imap.sync-now') }}">
            @csrf
            <button type="submit" class="text-xs bg-green-600 text-white font-medium px-4 py-1.5 rounded-lg hover:bg-green-700">
                ▶ Sync Now
            </button>
        </form>
        <form method="POST" action="{{ route('settings.email-imap.reset-sync-date') }}">
            @csrf
            <button type="submit" class="text-xs text-orange-600 border border-orange-300 px-3 py-1.5 rounded-lg hover:bg-orange-50"
                    onclick="return confirm('Reset sync date? Next sync will re-scan the last 7 days.')">
                Reset Sync Date
            </button>
        </form>
        <p class="text-xs text-gray-400">Configure cron-job.org to call the URL above every 15 minutes.</p>
    </div>
</div>

{{-- Per-User Accounts --}}
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h2 class="text-sm font-semibold text-gray-800 mb-4">User Accounts</h2>
    <div class="space-y-4">
        @foreach($allUsers as $u)
        <div class="border border-gray-100 rounded-xl overflow-hidden" x-data="{ open: {{ $u->imap_enabled ? 'true' : 'false' }} }">
            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 cursor-pointer" @click="open = !open">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-700">{{ $u->name }}</span>
                    <span class="text-xs text-gray-400">{{ $u->email }}</span>
                    @if($u->imap_enabled)
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Active</span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if($u->imap_last_sync_at)
                    <span class="text-xs text-gray-400">Last sync: {{ $u->imap_last_sync_at->diffForHumans() }}</span>
                    @endif
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
            </div>
            <div x-show="open" x-cloak class="px-4 py-4">
                <form method="POST" action="{{ route('settings.email-imap.update-user', $u) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">IMAP Host</label>
                            <input type="text" name="imap_host" value="{{ $u->imap_host }}" placeholder="mail.example.com"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
                            <input type="number" name="imap_port" value="{{ $u->imap_port ?? 993 }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Encryption</label>
                            <select name="imap_encryption" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                @foreach(['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'None'] as $val => $label)
                                <option value="{{ $val }}" {{ ($u->imap_encryption ?? 'ssl') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                            <input type="text" name="imap_user" value="{{ $u->imap_user }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Password <span class="text-gray-400">(blank = keep)</span></label>
                            <input type="password" name="imap_password" placeholder="••••••••"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="imap_enabled" value="1" {{ $u->imap_enabled ? 'checked' : '' }} class="rounded">
                            Enable sync for this user
                        </label>
                        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
