@extends('layouts.app')

@section('title', 'Şirketler')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">İç şirketler, servis sağlayıcılar ve agentlar.</p>
    <a href="{{ route('settings.companies.create') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Yeni Şirket
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('error') }}</div>
@endif

@php
$typeLabel = ['internal' => 'İç Şirket', 'service_provider' => 'Servis Sağlayıcı', 'agent' => 'Agent'];
$typeBadge = [
    'internal'         => 'bg-indigo-100 text-indigo-700',
    'service_provider' => 'bg-orange-100 text-orange-700',
    'agent'            => 'bg-gray-100 text-gray-600',
];
@endphp

@if($companies->isEmpty())
<div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
    <p class="text-gray-400 text-sm">Henüz şirket yok.</p>
    <a href="{{ route('settings.companies.create') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-2 inline-block">
        İlk şirketi ekle →
    </a>
</div>
@else
<div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
    @foreach($companies as $company)
    <div class="flex items-center gap-4 px-5 py-3.5">
        <div class="flex-1 min-w-0">
            <span class="text-sm font-medium text-gray-800">{{ $company->name }}</span>
            @if($company->domain)
            <span class="text-xs text-gray-400 ml-2">{{ $company->domain }}</span>
            @endif
        </div>
        <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $typeBadge[$company->type] ?? 'bg-gray-100 text-gray-600' }}">
            {{ $typeLabel[$company->type] ?? $company->type }}
        </span>
        <span class="text-xs text-gray-400">{{ $company->users_count }} kullanıcı</span>
        <div class="flex items-center gap-2">
            <a href="{{ route('settings.companies.edit', $company) }}"
               class="text-sm text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                Düzenle
            </a>
            <form method="POST" action="{{ route('settings.companies.destroy', $company) }}"
                  onsubmit="return confirm('{{ addslashes($company->name) }} silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
