@extends('layouts.app')

@section('title', 'Yeni Şirket')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="max-w-lg">
    <h2 class="text-base font-semibold text-gray-800 mb-5">Yeni Şirket</h2>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('settings.companies.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Şirket Adı</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tür</label>
            <select name="type" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Seçin...</option>
                <option value="internal"         {{ old('type') === 'internal'         ? 'selected' : '' }}>İç Şirket</option>
                <option value="service_provider" {{ old('type') === 'service_provider' ? 'selected' : '' }}>Servis Sağlayıcı</option>
                <option value="agent"            {{ old('type') === 'agent'            ? 'selected' : '' }}>Agent</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Domain <span class="text-gray-400 font-normal">(opsiyonel)</span></label>
            <input type="text" name="domain" value="{{ old('domain') }}" placeholder="ornek.com"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Oluştur
            </button>
            <a href="{{ route('settings.companies.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">İptal</a>
        </div>
    </form>
</div>
@endsection
