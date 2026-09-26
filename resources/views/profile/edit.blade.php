@extends('layouts.app')
@section('title', 'My Profile')
@section('heading', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6 space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">{{ session('error') }}</div>
    @endif

    {{-- Profile info --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Personal Information</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-medium text-gray-600 mb-3">Change Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span></p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Current Password</label>
                        <input type="password" name="current_password"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 @error('current_password') border-red-400 @enderror">
                        @error('current_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">New Password</label>
                            <input type="password" name="password"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="bg-indigo-600 text-white text-sm px-5 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Google Calendar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <svg viewBox="0 0 24 24" class="w-5 h-5 flex-shrink-0" fill="none">
                <path d="M19.5 3H18V1.5a.75.75 0 0 0-1.5 0V3h-9V1.5a.75.75 0 0 0-1.5 0V3H4.5A2.25 2.25 0 0 0 2.25 5.25v15A2.25 2.25 0 0 0 4.5 22.5h15a2.25 2.25 0 0 0 2.25-2.25v-15A2.25 2.25 0 0 0 19.5 3Z" fill="#4285F4"/>
                <path d="M2.25 9h19.5" stroke="#fff" stroke-width="1.5"/>
            </svg>
            <h2 class="text-sm font-semibold text-gray-700">Google Calendar</h2>
        </div>

        @if($user->hasGoogleCalendar())
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg mb-4">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                <div>
                    <p class="text-sm font-medium text-green-800">Connected</p>
                    @if($user->google_email)
                    <p class="text-xs text-green-600">{{ $user->google_email }}</p>
                    @endif
                    @if($user->google_connected_at)
                    <p class="text-xs text-gray-400">Since {{ $user->google_connected_at->format('d M Y') }}</p>
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('google.calendar.disconnect') }}">
                @csrf
                <button type="submit" class="text-xs text-red-600 hover:text-red-800 border border-red-200 rounded-lg px-3 py-1.5 hover:bg-red-50 transition-colors">
                    Disconnect
                </button>
            </form>
        </div>
        <p class="text-xs text-gray-400">
            When you're added to a meeting, it will automatically appear in your Google Calendar.
        </p>
        @else
        <p class="text-sm text-gray-500 mb-4">
            Connect your Google account to automatically sync meetings to your Google Calendar.
        </p>
        <a href="{{ route('google.calendar.redirect') }}"
           class="inline-flex items-center gap-2 bg-white border border-gray-300 text-sm text-gray-700 font-medium px-4 py-2.5 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors shadow-sm">
            <svg viewBox="0 0 24 24" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Connect Google Calendar
        </a>
        @endif
    </div>

</div>
@endsection
