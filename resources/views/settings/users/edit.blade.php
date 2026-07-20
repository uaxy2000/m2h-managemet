@extends('layouts.app')

@section('title', 'Kullanıcı Düzenle')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

<div class="max-w-lg">
    <h2 class="text-base font-semibold text-gray-800 mb-5">Kullanıcı Düzenle</h2>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('settings.users.update', $user) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ad Soyad</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">E-posta</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
            <select name="role" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin"       {{ old('role', $user->role) === 'admin'       ? 'selected' : '' }}>Admin</option>
                <option value="member"      {{ old('role', $user->role) === 'member'      ? 'selected' : '' }}>Member</option>
            </select>
        </div>

        @if($companies->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Şirket</label>
            <select name="company_id"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— Seçin —</option>
                @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) === $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Yeni Şifre <span class="text-gray-400 font-normal">(boş bırakılırsa değişmez)</span>
            </label>
            <input type="password" name="password" minlength="8"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Yeni Şifre (tekrar)</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Kaydet
            </button>
            <a href="{{ route('settings.users.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">İptal</a>
        </div>
    </form>
</div>
@endsection
