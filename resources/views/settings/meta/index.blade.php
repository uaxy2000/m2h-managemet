@extends('layouts.app')

@section('title', 'Meta Leads Integration')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

{{-- Webhook info --}}
<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6 flex items-start gap-3">
    <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
    </svg>
    <div>
        <p class="text-sm font-semibold text-indigo-800 mb-1">Webhook URL — Meta'ya gireceğiniz adres</p>
        <code class="text-xs text-indigo-700 bg-indigo-100 px-2 py-1 rounded font-mono select-all">
            {{ rtrim(config('app.url'), '/') }}/webhook/meta
        </code>
        <p class="text-xs text-indigo-600 mt-1.5">Verify Token: <span class="font-mono font-semibold">{{ config('services.meta.verify_token') }}</span></p>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('error') }}</div>
@endif

{{-- Add page form --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Facebook Sayfası Bağla</h3>
    <form method="POST" action="{{ route('settings.meta.pages.store') }}" class="grid grid-cols-3 gap-3 items-end">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Page ID</label>
            <input type="text" name="page_id" value="{{ old('page_id') }}" required placeholder="123456789012345"
                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('page_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Sayfa Adı</label>
            <input type="text" name="page_name" value="{{ old('page_name') }}" required placeholder="M2H Turkey"
                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Page Access Token</label>
            <input type="text" name="access_token" value="{{ old('access_token') }}" required placeholder="EAABxxxx..."
                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-xs">
        </div>
        <div class="col-span-3 flex justify-end">
            <button type="submit"
                    class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                Sayfayı Bağla
            </button>
        </div>
    </form>
</div>

{{-- Pages list --}}
@forelse($pages as $page)
<div class="bg-white rounded-xl border border-gray-200 mb-4" x-data="{ editOpen: false, mapOpen: false }">

    {{-- Page header --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
        <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $page->is_active ? 'bg-green-400' : 'bg-gray-300' }}"></div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-800">{{ $page->page_name }}</p>
            <p class="text-xs text-gray-400 font-mono">ID: {{ $page->page_id }}</p>
        </div>
        <span class="text-xs text-gray-400">{{ $page->form_mappings_count }} mapping</span>
        <button @click="mapOpen = !mapOpen"
                class="text-xs text-indigo-600 hover:text-indigo-800 px-3 py-1.5 rounded-lg border border-indigo-200 hover:bg-indigo-50 transition-colors">
            + Mapping Ekle
        </button>
        <button @click="editOpen = !editOpen"
                class="text-xs text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
            Düzenle
        </button>
        <form method="POST" action="{{ route('settings.meta.pages.destroy', $page) }}"
              onsubmit="return confirm('Bu sayfayı kaldır?')">
            @csrf @method('DELETE')
            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
            </button>
        </form>
    </div>

    {{-- Edit form --}}
    <div x-show="editOpen" x-cloak class="px-5 py-4 bg-gray-50 border-b border-gray-100">
        <form method="POST" action="{{ route('settings.meta.pages.update', $page) }}" class="grid grid-cols-2 gap-3 items-end">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sayfa Adı</label>
                <input type="text" name="page_name" value="{{ $page->page_name }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-center gap-2 pt-5">
                <input type="checkbox" name="is_active" id="active_{{ $page->id }}" value="1" {{ $page->is_active ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="active_{{ $page->id }}" class="text-sm text-gray-700">Aktif</label>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Access Token</label>
                <input type="text" name="access_token" value="{{ $page->access_token }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-xs">
            </div>
            <div class="col-span-2 flex justify-end gap-2">
                <button type="button" @click="editOpen = false"
                        class="text-sm text-gray-600 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">İptal</button>
                <button type="submit"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg transition-colors font-medium">Kaydet</button>
            </div>
        </form>
    </div>

    {{-- Add mapping form --}}
    <div x-show="mapOpen" x-cloak class="px-5 py-4 bg-blue-50 border-b border-blue-100">
        <p class="text-xs font-semibold text-blue-800 mb-3">Yeni Form Mapping</p>
        <form method="POST" action="{{ route('settings.meta.mappings.store', $page) }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Form ID <span class="text-gray-400">(boş = default)</span></label>
                    <input type="text" name="form_id" placeholder="123456789..."
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Form Adı <span class="text-gray-400">(hatırlatıcı)</span></label>
                    <input type="text" name="form_name" placeholder="Yaz 2025 Kampanyası"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3"
                 x-data="{
                     pipelineId: '',
                     stages: [],
                     pipelines: {{ $pipelines->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'stages' => $p->stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name])])->toJson() }},
                     updateStages() {
                         const p = this.pipelines.find(p => p.id === this.pipelineId);
                         this.stages = p ? p.stages : [];
                     }
                 }">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pipeline <span class="text-red-500">*</span></label>
                    <select name="pipeline_id" required x-model="pipelineId" @change="updateStages()"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seç…</option>
                        @foreach($pipelines as $pipeline)
                        <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Stage <span class="text-red-500">*</span></label>
                    <select name="stage_id" required
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Önce pipeline seç</option>
                        <template x-for="stage in stages" :key="stage.id">
                            <option :value="stage.id" x-text="stage.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            @if($tags->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Otomatik Eklenecek Tag'ler</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color:{{ $tag->color }}"></span>
                        <span class="text-xs text-gray-700">{{ $tag->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_default" value="1"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs text-gray-700">Bu sayfa için varsayılan mapping (form ID eşleşmezse kullanılır)</span>
                </label>
                <div class="flex gap-2">
                    <button type="button" @click="mapOpen = false"
                            class="text-sm text-gray-600 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50">İptal</button>
                    <button type="submit"
                            class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg transition-colors font-medium">Kaydet</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Existing mappings --}}
    @if($page->formMappings->isNotEmpty())
    <div class="divide-y divide-gray-100">
        @foreach($page->formMappings as $mapping)
        <div class="flex items-center gap-4 px-5 py-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($mapping->is_default)
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Varsayılan</span>
                    @endif
                    @if($mapping->form_name)
                    <span class="text-xs font-medium text-gray-700">{{ $mapping->form_name }}</span>
                    @endif
                    @if($mapping->form_id)
                    <span class="text-xs text-gray-400 font-mono">{{ $mapping->form_id }}</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $mapping->pipeline?->name }}
                    <span class="mx-1 text-gray-300">→</span>
                    {{ $mapping->stage?->name }}
                    @if(!empty($mapping->tag_ids))
                    <span class="ml-2">
                        @foreach($mapping->tag_ids as $tagId)
                        @php $t = $tags->firstWhere('id', $tagId); @endphp
                        @if($t)
                        <span class="inline-block w-2 h-2 rounded-full" style="background-color:{{ $t->color }}" title="{{ $t->name }}"></span>
                        @endif
                        @endforeach
                    </span>
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('settings.meta.mappings.destroy', [$page, $mapping]) }}"
                  onsubmit="return confirm('Bu mapping\'i sil?')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </form>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-xs text-gray-400 px-5 py-3">Henüz mapping yok.</p>
    @endif

</div>
@empty
<div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
    <p class="text-gray-400 text-sm">Henüz bağlı Facebook sayfası yok.</p>
    <p class="text-gray-400 text-xs mt-1">Yukarıdaki formu kullanarak bir sayfa ekleyin.</p>
</div>
@endforelse

@endsection
