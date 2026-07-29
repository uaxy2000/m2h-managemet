@extends('layouts.app')
@section('title', 'WA Şablonları')
@section('heading', 'WhatsApp Şablonları')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Toolbar --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Meta Business Manager'dan onaylı şablonları senkronize edin.</p>
        <form method="POST" action="{{ route('settings.wa-templates.sync') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Meta'dan Senkronize Et
            </button>
        </form>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-2.5 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-2.5 text-sm">{{ session('error') }}</div>
    @endif

    @forelse($templates as $tpl)
    @php
        $bodyText  = $tpl->bodyText();
        $varCount  = $tpl->bodyVariableCount();
        $hdrType   = $tpl->headerType();
        $fields    = $tpl->parameter_fields ?? [];
        $available = \App\Models\WaTemplate::availableFields();
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ open: false }">

        {{-- Header row --}}
        <div class="flex items-center gap-4 px-5 py-4 cursor-pointer hover:bg-gray-50 transition-colors"
             @click="open = !open">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-gray-800">
                        {{ $tpl->display_name ?? $tpl->name }}
                    </span>
                    @if($tpl->display_name && $tpl->display_name !== $tpl->name)
                    <span class="text-xs text-gray-400 font-mono">{{ $tpl->name }}</span>
                    @endif
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Onaylı</span>
                    @if($hdrType === 'IMAGE')
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Görsel başlık</span>
                    @endif
                    @if($varCount > 0)
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ $varCount }} değişken</span>
                    @endif
                    @if(!$tpl->is_active)
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Pasif</span>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ Str::limit($bodyText, 100) }}</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </div>

        {{-- Expand: config form --}}
        <div x-show="open" x-cloak class="border-t border-gray-100 px-5 py-5 space-y-5">

            {{-- Body preview --}}
            <div class="bg-gray-50 rounded-lg px-4 py-3 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $bodyText }}</div>

            <form method="POST" action="{{ route('settings.wa-templates.update', $tpl) }}" class="space-y-4">
                @csrf @method('PUT')

                {{-- Display name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Görünen ad (isteğe bağlı)</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $tpl->display_name) }}"
                           placeholder="{{ $tpl->name }}"
                           class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Header image URL --}}
                @if($hdrType === 'IMAGE')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Başlık görseli URL</label>
                    <input type="url" name="header_image_url" value="{{ old('header_image_url', $tpl->header_image_url) }}"
                           placeholder="https://..."
                           class="w-full rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Herkese açık, kalıcı bir görsel URL'si olmalı.</p>
                </div>
                @endif

                {{-- Variable mapping --}}
                @if($varCount > 0)
                <div class="space-y-2">
                    <p class="text-xs font-medium text-gray-600">Değişken eşlemeleri</p>
                    @for($i = 1; $i <= $varCount; $i++)
                    @php
                        $mapped = collect($fields)->firstWhere(fn($f) => $f['component'] === 'body' && (int)$f['index'] === $i);
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-mono text-amber-700 bg-amber-50 px-2 py-1 rounded w-12 text-center flex-shrink-0">&#123;&#123;{{ $i }}&#125;&#125;</span>
                        <input type="hidden" name="parameter_fields[{{ $i - 1 }}][component]" value="body">
                        <input type="hidden" name="parameter_fields[{{ $i - 1 }}][index]" value="{{ $i }}">
                        <select name="parameter_fields[{{ $i - 1 }}][field]"
                                class="flex-1 rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">— Seçin</option>
                            @foreach($available as $value => $label)
                            <option value="{{ $value }}" @selected(($mapped['field'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endfor
                </div>
                @endif

                {{-- Active toggle --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="active_{{ $tpl->id }}"
                           @checked($tpl->is_active)
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="active_{{ $tpl->id }}" class="text-sm text-gray-700">Aktif (lead sayfasında göster)</label>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center">
        <p class="text-sm text-gray-400">Henüz şablon yok. "Meta'dan Senkronize Et" butonuna basın.</p>
    </div>
    @endforelse

</div>
@endsection
