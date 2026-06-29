@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="min-h-full flex flex-col items-center justify-center py-12 px-4">

    {{-- Brand --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">M2H Management</h1>
        <p class="text-sm text-gray-500 mt-1">CRM & Operations Platform</p>
    </div>

    {{-- Card --}}
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

        <h2 class="text-xl font-semibold text-gray-800 mb-6">Sign in to your account</h2>

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }">
            @csrf

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Email address
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="block w-full rounded-lg text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                @error('email')
                    <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Password
                </label>
                <div class="relative">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm pr-10 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors"
                        tabindex="-1"
                    >
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-show="showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Remember me --}}
            <div class="flex items-center mb-6">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                >
                <label for="remember" class="ml-2 text-sm text-gray-600">
                    Remember me
                </label>
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Sign In
            </button>

        </form>
    </div>

</div>
@endsection
