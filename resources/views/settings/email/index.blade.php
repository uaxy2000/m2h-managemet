@extends('layouts.app')
@section('title', 'Email Settings')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="max-w-xl space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.email.update') }}">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">SMTP Settings</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Host <span class="text-red-500">*</span></label>
                    <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host'] ?? '') }}"
                           placeholder="mail.example.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    @error('mail_host')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Port <span class="text-red-500">*</span></label>
                    <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port'] ?? '587') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    @error('mail_port')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Encryption</label>
                <select name="mail_encryption" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    @foreach(['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'] as $val => $label)
                    <option value="{{ $val }}" {{ ($settings['mail_encryption'] ?? 'tls') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Username (email address) <span class="text-red-500">*</span></label>
                <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username'] ?? '') }}"
                       placeholder="info@m2h.ge"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                @error('mail_username')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                <input type="password" name="mail_password" value=""
                       placeholder="{{ isset($settings['mail_password']) ? '••••••••' : 'Enter password' }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <p class="text-xs text-gray-400 mt-1">Leave blank to keep current password.</p>
                @error('mail_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">From Address <span class="text-red-500">*</span></label>
                    <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}"
                           placeholder="info@m2h.ge"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    @error('mail_from_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">From Name <span class="text-red-500">*</span></label>
                    <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'M2H Management') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    @error('mail_from_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </div>
    </form>

    {{-- Test send --}}
    <form method="POST" action="{{ route('settings.email.test') }}">
        @csrf
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-700">Send Test Email</p>
                <p class="text-xs text-gray-400 mt-0.5">Sends a test to <strong>{{ auth()->user()->email }}</strong></p>
            </div>
            <button type="submit" class="text-sm font-medium text-indigo-600 border border-indigo-300 px-4 py-2 rounded-lg hover:bg-indigo-50 transition-colors">
                Send Test
            </button>
        </div>
    </form>

</div>
@endsection
