@extends('layouts.app')

@section('title', 'Board Düzenle')
@section('heading', $board->title)

@section('content')
<div class="max-w-xl mx-auto px-4 py-6">
    <form method="POST" action="{{ route('boards.update', $board) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Başlık <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $board->title) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description', $board->description) }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Erişim İzinleri</h3>
            @include('boards._permission_form')
        </div>

        <div class="flex items-center justify-between">
            <form method="POST" action="{{ route('boards.destroy', $board) }}"
                  onsubmit="return confirm('Bu board\'u silmek istediğinize emin misiniz?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                    Board'u Sil
                </button>
            </form>

            <div class="flex gap-3">
                <a href="{{ route('boards.show', $board) }}"
                   class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    İptal
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                    Kaydet
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
