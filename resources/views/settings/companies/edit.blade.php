@extends('layouts.app')

@section('title', 'Şirket Düzenle')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="max-w-lg">
    <h2 class="text-base font-semibold text-gray-800 mb-5">{{ $company->name }}</h2>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('settings.companies.update', $company) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4 mb-6">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Şirket Adı</label>
            <input type="text" name="name" value="{{ old('name', $company->name) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tür</label>
            <select name="type" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="internal"         {{ old('type', $company->type) === 'internal'         ? 'selected' : '' }}>İç Şirket</option>
                <option value="service_provider" {{ old('type', $company->type) === 'service_provider' ? 'selected' : '' }}>Servis Sağlayıcı</option>
                <option value="agent"            {{ old('type', $company->type) === 'agent'            ? 'selected' : '' }}>Agent</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Domain <span class="text-gray-400 font-normal">(opsiyonel)</span></label>
            <input type="text" name="domain" value="{{ old('domain', $company->domain) }}" placeholder="ornek.com"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Kaydet
            </button>
            <a href="{{ route('settings.companies.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">İptal</a>
        </div>
    </form>

    {{-- Users of this company --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Kullanıcılar ({{ $users->count() }})</h3>
            <a href="{{ route('settings.users.create') }}"
               class="text-xs text-indigo-600 hover:text-indigo-800">+ Yeni kullanıcı ekle</a>
        </div>
        @if($users->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-5 py-8 text-center">
            <p class="text-gray-400 text-sm">Bu şirkete bağlı kullanıcı yok.</p>
        </div>
        @else
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach($users as $user)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                </div>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $user->roleBadgeColor() }}">
                    {{ $user->roleLabel() }}
                </span>
                <a href="{{ route('settings.users.edit', $user) }}"
                   class="text-xs text-gray-500 hover:text-gray-700 px-2.5 py-1 rounded border border-gray-200 hover:bg-gray-50">
                    Düzenle
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
