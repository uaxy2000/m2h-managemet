@extends('layouts.app')

@section('title', 'Yeni Board')
@section('heading', 'Yeni Board')

@section('content')
<div class="max-w-xl mx-auto px-4 py-6">
    <form method="POST" action="{{ route('boards.store') }}" class="space-y-5">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Başlık <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Erişim İzinleri</h3>
            @include('boards._permission_form')
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('boards.index') }}"
               class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                İptal
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                Oluştur
            </button>
        </div>
    </form>
</div>
@endsection
