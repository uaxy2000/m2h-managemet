@extends('layouts.app')

@section('title', $lead->fullName())
@section('heading', 'Leads')

@section('content')

{{-- Breadcrumb + actions --}}
<div class="flex items-center justify-between mb-5">
    <a href="{{ route('leads.index', ['pipeline' => $lead->pipeline_id]) }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Leads
    </a>
    <div class="flex items-center gap-2">
        <button onclick="window.location.reload()"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 px-3 py-2 rounded-lg
                       border border-gray-200 hover:bg-gray-50 transition-colors" title="Refresh">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
        </button>
        <a href="{{ route('leads.edit', $lead) }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-600 px-3.5 py-2 rounded-lg
                  border border-gray-200 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
            </svg>
            Edit
        </a>
        <form method="POST" action="{{ route('leads.destroy', $lead) }}"
              onsubmit="return confirm('Delete {{ addslashes($lead->fullName()) }}? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 text-sm text-red-600 px-3.5 py-2 rounded-lg
                           border border-red-200 hover:bg-red-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
                Delete
            </button>
        </form>
    </div>
</div>

{{-- Flash messages --}}
@foreach(['success', 'note_success', 'task_success'] as $key)
@if(session($key))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-2.5 text-sm mb-5">
    {{ session($key) }}
</div>
@endif
@endforeach
@if(session('note_error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-2.5 text-sm mb-5">
    {{ session('note_error') }}
</div>
@endif
@if(session('warning'))
<div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-2.5 text-sm mb-5">
    {{ session('warning') }}
</div>
@endif

{{-- Lead header --}}
@php
    $currentTagIds = $lead->tags->pluck('id')->values()->toArray();
    $tagsByGroup   = $allTags->groupBy(fn ($t) => $t->group?->name ?? '');
    $tagsGrouped   = $tagsByGroup->filter(fn ($v, $k) => $k !== '')->sortKeys();
    $tagsUngrouped = $tagsByGroup->get('', collect());
@endphp

<div x-data="{
        open: false,
        selected: {{ json_encode($currentTagIds) }},
        toggle(id) {
            const idx = this.selected.indexOf(id);
            idx >= 0 ? this.selected.splice(idx, 1) : this.selected.push(id);
        },
        isSelected(id) { return this.selected.includes(id); }
     }"
     class="mb-5">

    {{-- Header row --}}
    <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center text-white text-lg font-bold flex-shrink-0 mt-0.5">
            {{ $lead->initials() }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-start sm:gap-4 sm:justify-between gap-2">

                {{-- Left: name, badges, pipeline info --}}
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl font-bold text-gray-900">{{ $lead->fullName() }}</h2>
                        @if($lead->is_duplicate_flag)
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                            </svg>
                            Possible duplicate
                        </span>
                        @endif
                        @if($lead->meta_platform === 'ig')
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-pink-700 bg-pink-100 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            Instagram
                        </span>
                        @elseif($lead->meta_platform === 'fb')
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $lead->pipeline?->name }}
                        @if($lead->stage)
                        <span class="mx-1.5 text-gray-300">·</span>
                        <span class="font-medium" style="color: {{ $lead->stage->color }}">{{ $lead->stage->name }}</span>
                        @endif
                        @if($lead->subStage)
                        <span class="mx-1.5 text-gray-300">·</span>{{ $lead->subStage->name }}
                        @endif
                    </p>
                    @if($lead->meta_ad_name || $lead->meta_campaign_name)
                    <p class="text-xs text-gray-400 mt-0.5">
                        @if($lead->meta_ad_name)<span title="Ad">{{ $lead->meta_ad_name }}</span>@endif
                        @if($lead->meta_ad_name && $lead->meta_campaign_name)<span class="mx-1 text-gray-300">·</span>@endif
                        @if($lead->meta_campaign_name)<span title="Campaign" class="text-gray-300">{{ $lead->meta_campaign_name }}</span>@endif
                    </p>
                    @endif
                    @if($lead->meta_ad_id)
                    <p class="text-xs text-gray-300 mt-0.5 font-mono" title="Ad ID">fb{{ $lead->meta_ad_id }}</p>
                    @endif
                </div>

                {{-- Right: Tags --}}
                <div class="sm:flex-shrink-0 sm:text-right sm:max-w-xs">
                    <div class="flex flex-wrap gap-1.5 sm:justify-end mb-2">
                        @forelse($lead->tags as $tag)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium text-white whitespace-nowrap"
                              style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                        @empty
                        <span class="text-xs text-gray-400">No tags</span>
                        @endforelse
                    </div>
                    @if($allTags->isNotEmpty())
                    <button @click="open = true" type="button"
                            class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                        </svg>
                        Edit tags
                    </button>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Tag edit modal --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="open = false">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Edit Tags</h3>
                <button @click="open = false" type="button"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-4 space-y-5">
                @foreach($tagsGrouped as $groupName => $groupTags)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $groupName }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($groupTags as $tag)
                        <button type="button" @click="toggle('{{ $tag->id }}')"
                                :class="isSelected('{{ $tag->id }}')
                                    ? 'text-white border-transparent'
                                    : 'text-gray-500 border-gray-200 bg-white hover:border-gray-400'"
                                :style="isSelected('{{ $tag->id }}') ? 'background:{{ $tag->color }};border-color:{{ $tag->color }}' : ''"
                                class="px-3 py-1 rounded-full text-xs font-medium border transition-all">
                            {{ $tag->name }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endforeach

                @if($tagsUngrouped->isNotEmpty())
                <div class="{{ $tagsGrouped->isNotEmpty() ? 'border-t border-gray-100 pt-4' : '' }}">
                    @if($tagsGrouped->isNotEmpty())
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Other</p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        @foreach($tagsUngrouped as $tag)
                        <button type="button" @click="toggle('{{ $tag->id }}')"
                                :class="isSelected('{{ $tag->id }}')
                                    ? 'text-white border-transparent'
                                    : 'text-gray-500 border-gray-200 bg-white hover:border-gray-400'"
                                :style="isSelected('{{ $tag->id }}') ? 'background:{{ $tag->color }};border-color:{{ $tag->color }}' : ''"
                                class="px-3 py-1 rounded-full text-xs font-medium border transition-all">
                            {{ $tag->name }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400" x-text="selected.length + ' tag' + (selected.length !== 1 ? 's' : '') + ' selected'"></span>
                <div class="flex gap-2">
                    <button @click="open = false" type="button"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('leads.tags.sync', $lead) }}">
                        @csrf @method('PUT')
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="tag_ids[]" :value="id">
                        </template>
                        <button type="submit"
                                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                            Save
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Stage History — horizontal strip --}}
@if($lead->statusHistory->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200 px-5 py-4 mb-5 overflow-x-auto">
    <div class="flex items-start min-w-max">
        @foreach($lead->statusHistory as $entry)
        @php $isLast = $loop->last; $color = $entry->toStage?->color ?? '#6366f1'; @endphp
        <div class="flex items-start">
            <div class="flex flex-col items-center text-center w-28">
                <div class="w-3 h-3 rounded-full flex-shrink-0 mb-1.5 {{ $isLast ? 'ring-2 ring-offset-2' : '' }}"
                     style="background: {{ $color }}; {{ $isLast ? 'ring-color:'.$color : '' }}"></div>
                <p class="text-xs font-medium leading-tight {{ $isLast ? 'text-gray-900' : 'text-gray-500' }}">
                    {{ $entry->toStage?->name ?? '—' }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5 whitespace-nowrap">
                    {{ $entry->changed_at->format('d M Y') }}<br>
                    <span class="text-gray-300">{{ $entry->changed_at->format('H:i') }}</span>
                </p>
            </div>
            @if(!$isLast)
            <div class="flex-shrink-0 mt-1.5 mx-1">
                <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Two-column layout: 40% left | 60% right --}}
<div class="flex flex-col lg:flex-row gap-5 items-start">

    {{-- Left column (40%) — on mobile drops below timeline --}}
    <div class="space-y-5 w-full lg:w-2/5 lg:flex-shrink-0 order-2 lg:order-1">

        {{-- Contact --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Contact</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                @if($lead->email)
                <div>
                    <dt class="text-xs text-gray-400">Email</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">
                        <a href="mailto:{{ $lead->email }}" class="hover:text-indigo-600 transition-colors">{{ $lead->email }}</a>
                    </dd>
                </div>
                @endif
                @if($lead->phone)
                <div>
                    <dt class="text-xs text-gray-400">Phone</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $lead->phone }}</dd>
                </div>
                @endif
                @if($lead->whatsapp)
                <div>
                    <dt class="text-xs text-gray-400">WhatsApp</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $lead->whatsapp }}</dd>
                </div>
                @endif
                @if($lead->country_of_origin)
                <div>
                    <dt class="text-xs text-gray-400">Country of Origin</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $lead->country_of_origin }}</dd>
                </div>
                @endif
                @if($lead->nationality)
                <div>
                    <dt class="text-xs text-gray-400">Nationality</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $lead->nationality }}</dd>
                </div>
                @endif
                @if($lead->language)
                <div>
                    <dt class="text-xs text-gray-400">Language</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $lead->language }}</dd>
                </div>
                @endif
            </dl>
            @if(!$lead->email && !$lead->phone && !$lead->whatsapp && !$lead->country_of_origin && !$lead->nationality && !$lead->language)
            <p class="text-sm text-gray-400">No contact details.</p>
            @endif
        </div>

        {{-- Assignment --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Assignment</h3>

            @php
            $pencilSvg = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>';
            @endphp

            <div class="grid grid-cols-2 gap-x-5 gap-y-6">

                {{-- Internal --}}
                <div x-data="{editing:false}">
                    <div class="flex items-center gap-1 mb-1">
                        <p class="text-xs text-gray-400">Internal</p>
                        @if($canManageAssignment)
                        <button @click="editing=!editing" type="button" class="text-gray-300 hover:text-indigo-500 transition-colors flex items-center">
                            <span x-show="!editing">{!! $pencilSvg !!}</span>
                            <span x-show="editing" x-cloak class="text-xs text-indigo-500 leading-none">✕</span>
                        </button>
                        @endif
                    </div>
                    <div x-show="!editing">
                        <p class="text-sm font-medium text-gray-800">{{ $lead->assignedTo?->name ?? '—' }}</p>
                    </div>
                    @if($canManageAssignment)
                    <div x-show="editing" x-cloak>
                        <form method="POST" action="{{ route('leads.assign-user', $lead) }}" class="flex gap-1.5">
                            @csrf @method('PATCH')
                            <select name="assigned_to" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">— None —</option>
                                @foreach($internalUsers as $u)
                                <option value="{{ $u->id }}" {{ $lead->assigned_to === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-2.5 py-1.5 rounded-lg transition-colors font-medium">✓</button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Service Provider --}}
                <div x-data="{editing:false}">
                    <div class="flex items-center gap-1 mb-1">
                        <p class="text-xs text-gray-400">Service Provider</p>
                        @if($canChangeServiceProvider && $serviceProviders->isNotEmpty())
                        <button @click="editing=!editing" type="button" class="text-gray-300 hover:text-indigo-500 transition-colors flex items-center">
                            <span x-show="!editing">{!! $pencilSvg !!}</span>
                            <span x-show="editing" x-cloak class="text-xs text-indigo-500 leading-none">✕</span>
                        </button>
                        @endif
                    </div>
                    <div x-show="!editing">
                        <p class="text-sm font-medium text-gray-800">{{ $lead->serviceProvider?->name ?? '—' }}</p>
                    </div>
                    @if($canChangeServiceProvider)
                    <div x-show="editing" x-cloak>
                        <form method="POST" action="{{ route('leads.assign-company', $lead) }}" class="flex gap-1.5">
                            @csrf @method('PATCH')
                            <input type="hidden" name="field" value="service_provider_id">
                            <select name="company_id" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">— None —</option>
                                @foreach($serviceProviders as $sp)
                                <option value="{{ $sp->id }}" {{ $lead->service_provider_id === $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-2.5 py-1.5 rounded-lg transition-colors font-medium">✓</button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Agent --}}
                <div x-data="{editing:false}">
                    <div class="flex items-center gap-1 mb-1">
                        <p class="text-xs text-gray-400">Agent</p>
                        @if($canManageAssignment && $agents->isNotEmpty())
                        <button @click="editing=!editing" type="button" class="text-gray-300 hover:text-indigo-500 transition-colors flex items-center">
                            <span x-show="!editing">{!! $pencilSvg !!}</span>
                            <span x-show="editing" x-cloak class="text-xs text-indigo-500 leading-none">✕</span>
                        </button>
                        @endif
                    </div>
                    <div x-show="!editing">
                        <p class="text-sm font-medium text-gray-800">{{ $lead->agent?->name ?? '—' }}</p>
                    </div>
                    @if($canManageAssignment)
                    <div x-show="editing" x-cloak>
                        <form method="POST" action="{{ route('leads.assign-company', $lead) }}" class="flex gap-1.5">
                            @csrf @method('PATCH')
                            <input type="hidden" name="field" value="agent_id">
                            <select name="company_id" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">— None —</option>
                                @foreach($agents as $ag)
                                <option value="{{ $ag->id }}" {{ $lead->agent_id === $ag->id ? 'selected' : '' }}>{{ $ag->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-2.5 py-1.5 rounded-lg transition-colors font-medium">✓</button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Stage --}}
                @if($canManageAssignment)
                <div x-data="{editing:false}">
                    <div class="flex items-center gap-1 mb-1">
                        <p class="text-xs text-gray-400">Stage</p>
                        <button @click="editing=!editing" type="button" class="text-gray-300 hover:text-indigo-500 transition-colors flex items-center">
                            <span x-show="!editing">{!! $pencilSvg !!}</span>
                            <span x-show="editing" x-cloak class="text-xs text-indigo-500 leading-none">✕</span>
                        </button>
                    </div>
                    <div x-show="!editing">
                        <p class="text-sm font-medium text-gray-800">{{ $lead->stage?->name ?? '—' }}</p>
                        @if($lead->pipeline)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $lead->pipeline->name }}</p>
                        @endif
                    </div>
                    <div x-show="editing" x-cloak>
                        <form @submit.prevent="
                            fetch('{{ route('leads.move', $lead) }}', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                                body: JSON.stringify({stage_id: $el.querySelector('[name=stage_id]').value})
                            }).then(() => window.location.reload())
                        " class="flex gap-1.5">
                            <select name="stage_id" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach($pipelines as $pl)
                                <optgroup label="{{ $pl->name }}">
                                    @foreach($pl->stages as $s)
                                    <option value="{{ $s->id }}" @selected($s->id === $lead->stage_id)>{{ $s->name }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            <button type="submit" class="flex-shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-2.5 py-1.5 rounded-lg transition-colors font-medium">✓</button>
                        </form>
                    </div>
                </div>
                @endif

            </div>
        </div>

        {{-- Programs --}}
        @php $sortedPrograms = $lead->programs->sortByDesc('pivot.is_primary'); @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Programs</h3>

            @forelse($sortedPrograms as $program)
            <div class="flex items-center gap-3 py-2.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                @if($program->pivot->is_primary)
                <span class="text-amber-400 flex-shrink-0" title="Primary program">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                    </svg>
                </span>
                @else
                <form method="POST" action="{{ route('leads.programs.primary', [$lead, $program->pivot->id]) }}">
                    @csrf
                    <button type="submit" title="Set as primary"
                            class="text-gray-200 hover:text-amber-400 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                        </svg>
                    </button>
                </form>
                @endif

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $program->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $program->country }}
                        <span class="mx-1 text-gray-200">·</span>
                        {{ $program->typeLabel() }}
                        @if($program->min_investment)
                        <span class="mx-1 text-gray-200">·</span>
                        Min. {{ $program->currency }} {{ number_format((float) $program->min_investment) }}
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('leads.programs.destroy', [$lead, $program->pivot->id]) }}"
                      onsubmit="return confirm('Remove {{ addslashes($program->name) }} from this lead?')">
                    @csrf @method('DELETE')
                    <button type="submit" title="Remove program"
                            class="text-gray-300 hover:text-red-500 transition-colors flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-400">No programs attached yet.</p>
            @endforelse

            @if($availablePrograms->isNotEmpty())
            <form method="POST" action="{{ route('leads.programs.store', $lead) }}"
                  class="flex gap-2 mt-4 {{ $sortedPrograms->isNotEmpty() ? 'pt-4 border-t border-gray-100' : '' }}">
                @csrf
                <select name="program_id" required
                        class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Add program…</option>
                    @foreach($availablePrograms->groupBy('country') as $country => $progs)
                    <optgroup label="{{ $country }}">
                        @foreach($progs as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                <button type="submit"
                        class="flex-shrink-0 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg transition-colors font-medium">
                    Add
                </button>
            </form>
            @elseif(auth()->user()->isAdmin() && $availablePrograms->isEmpty() && $lead->programs->isEmpty())
            <a href="{{ route('settings.programs.create') }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                Create programs in Settings →
            </a>
            @endif

            @if(session('program_error'))
            <p class="text-red-500 text-xs mt-2">{{ session('program_error') }}</p>
            @endif
        </div>

        {{-- Meta Form Responses --}}
        @if(!empty($lead->meta_form_data))
        @php
        $metaLabels   = config('meta_fields', []);
        $normalizeKey = fn(string $s) => mb_strtolower(str_replace(['İ', 'I'], 'i', $s), 'UTF-8');
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Form Responses</h3>
            <dl class="space-y-3">
                @foreach($lead->meta_form_data as $key => $value)
                @php
                $label = $metaLabels[$normalizeKey($key)]
                    ?? ucwords(str_replace(['_', '-'], ' ', $key));
                @endphp
                <div>
                    <dt class="text-xs text-gray-400">{{ $label }}</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $value ?? '—' }}</dd>
                </div>
                @endforeach
            </dl>
        </div>
        @endif

        {{-- Custom Fields --}}
        @if($customFields->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-5"
             x-data="customFieldsEditor({{ json_encode(
                $customFields->map(function ($f) use ($customValuesByKey) {
                    $cv = $customValuesByKey[$f->key] ?? null;
                    return [
                        'key'              => $f->key,
                        'type'             => $f->type,
                        'value'            => $f->type === 'multi_select'
                            ? (json_decode($cv?->value ?? '[]', true) ?? [])
                            : ($cv?->value ?? ''),
                        'exclusive_values' => $f->options->where('is_exclusive', true)->pluck('value')->values()->toArray(),
                    ];
                })->keyBy('key')
             ) }})">

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Custom Fields</h3>
                <button type="button" x-show="!editing" @click="editing = true"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                    Edit
                </button>
            </div>

            {{-- Read mode --}}
            <dl class="space-y-3" x-show="!editing">
                @foreach($customFields as $field)
                @php
                    $cv       = $customValuesByKey[$field->key] ?? null;
                    $rawValue = $cv?->value;
                    if ($field->type === 'multi_select') {
                        $vals    = json_decode($rawValue ?? '[]', true) ?? [];
                        $display = $field->options->whereIn('value', $vals)->pluck('label')->join(', ');
                    } elseif ($field->type === 'select') {
                        $display = $field->options->firstWhere('value', $rawValue)?->label ?? $rawValue;
                    } else {
                        $display = $rawValue;
                    }
                @endphp
                <div>
                    <dt class="text-xs text-gray-400">{{ $field->label }}</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $display ?: '—' }}</dd>
                </div>
                @endforeach
            </dl>

            {{-- Edit mode --}}
            <form x-show="editing" x-cloak method="POST"
                  action="{{ route('leads.custom-values.update', $lead) }}">
                @csrf @method('PATCH')

                <div class="space-y-4">
                @foreach($customFields as $field)
                <div>
                    <label class="block text-xs text-gray-500 mb-1.5">{{ $field->label }}</label>

                    @if($field->type === 'date')
                    <input type="text" name="custom[{{ $field->key }}]"
                           x-model="fields['{{ $field->key }}'].value"
                           placeholder="YYYY or YYYY-MM or YYYY-MM-DD"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                    @elseif($field->type === 'text')
                    <input type="text" name="custom[{{ $field->key }}]"
                           x-model="fields['{{ $field->key }}'].value"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                    @elseif($field->type === 'select')
                    <select name="custom[{{ $field->key }}]"
                            x-model="fields['{{ $field->key }}'].value"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Not set —</option>
                        @foreach($field->options as $opt)
                        <option value="{{ $opt->value }}">{{ $opt->label }}</option>
                        @endforeach
                    </select>

                    @elseif($field->type === 'multi_select')
                    <div class="flex flex-wrap gap-2">
                        @foreach($field->options as $opt)
                        <button type="button"
                                @click="toggleMulti('{{ $field->key }}', '{{ $opt->value }}', {{ $opt->is_exclusive ? 'true' : 'false' }})"
                                :class="fields['{{ $field->key }}'].value.includes('{{ $opt->value }}')
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400'"
                                class="px-3 py-1.5 text-xs font-medium rounded-full border transition-all">
                            {{ $opt->label }}
                        </button>
                        @endforeach
                    </div>
                    <template x-for="v in fields['{{ $field->key }}'].value" :key="v">
                        <input type="hidden" name="custom[{{ $field->key }}][]" :value="v">
                    </template>

                    @endif
                </div>
                @endforeach
                </div>

                <div class="flex gap-2 mt-5 pt-4 border-t border-gray-100">
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                        Save
                    </button>
                    <button type="button" @click="editing = false; reset()"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Deal --}}
        @if($lead->potential_value || $lead->our_commission || $lead->expected_close_date)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Deal</h3>
            <dl class="grid grid-cols-3 gap-x-6 gap-y-3">
                @if($lead->potential_value)
                <div>
                    <dt class="text-xs text-gray-400">Potential Value</dt>
                    <dd class="text-base font-semibold text-emerald-600 mt-0.5">${{ number_format((float) $lead->potential_value) }}</dd>
                </div>
                @endif
                @if($lead->our_commission)
                <div>
                    <dt class="text-xs text-gray-400">Our Commission</dt>
                    <dd class="text-base font-semibold text-indigo-600 mt-0.5">${{ number_format((float) $lead->our_commission) }}</dd>
                </div>
                @endif
                @if($lead->expected_close_date)
                <div>
                    <dt class="text-xs text-gray-400">Expected Close</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">{{ $lead->expected_close_date->format('d M Y') }}</dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

    </div>

    {{-- Right column (60%) — on mobile appears first --}}
    <div class="space-y-5 min-w-0 w-full lg:flex-1 order-1 lg:order-2">

        {{-- Overdue task alert --}}
        @php
            $openTasks    = $lead->tasks->where('is_done', false)->sortBy('due_at');
            $overdueTasks = $openTasks->filter(fn ($t) => $t->due_at && $t->due_at->isPast());
        @endphp
        @if($overdueTasks->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-red-700">
                    {{ $overdueTasks->count() }} overdue task{{ $overdueTasks->count() > 1 ? 's' : '' }}
                </p>
                <ul class="mt-0.5 space-y-0.5">
                    @foreach($overdueTasks as $ot)
                    <li class="text-xs text-red-600">
                        {{ $ot->title }}
                        <span class="text-red-400">· due {{ $ot->due_at->diffForHumans() }}</span>
                        @if($ot->assignedTo)
                        <span class="text-red-400">· {{ $ot->assignedTo->name }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Timeline (internal users only) --}}
        @if(auth()->user()->isInternal())
        <div class="bg-white rounded-xl border border-indigo-200 shadow-md shadow-indigo-50" id="timeline">

            <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Timeline</h3>
                <span class="text-xs text-gray-400">oldest → newest</span>
            </div>

            @if($timeline->isEmpty())
            <div class="px-5 py-8 text-center">
                <p class="text-sm text-gray-400">No activity yet.</p>
            </div>
            @else
            <div class="px-5 py-4 space-y-5">
                @foreach($timeline as $entry)
                @php $item = $entry['item'] ?? null; $entryType = $entry['type']; @endphp

                @if($entryType === 'note')
                {{-- Note --}}
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center
                                text-white text-xs font-semibold flex-shrink-0 mt-0.5">
                        {{ strtoupper(substr($item->createdBy->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-xs font-medium text-gray-700">{{ $item->createdBy->name }}</span>
                            <span class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</span>
                            @php $visParts = array_map('trim', explode(',', $item->visibility ?? 'internal')); @endphp
                            @if(in_array('internal', $visParts))
                            <span class="text-xs text-gray-300">· internal</span>
                            @else
                            <span class="text-xs text-emerald-600">· shared</span>
                            @endif
                        </div>
                        <div class="mt-1.5 bg-gray-50 rounded-lg px-3 py-2.5">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $item->content }}</p>
                        </div>
                        @php
                            $canDeleteNote = auth()->user()->isAdmin()
                                || ($item->created_by === auth()->id() && $item->created_at->diffInHours(now()) < 12);
                        @endphp
                        <div class="flex items-center gap-3 mt-1">
                        @if($canDeleteNote)
                        <form method="POST" action="{{ route('leads.notes.destroy', [$lead, $item]) }}"
                              onsubmit="return confirm('Delete this note?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-gray-300 hover:text-red-500 transition-colors">Delete</button>
                        </form>
                        @elseif($item->created_by === auth()->id())
                        <span class="text-xs text-gray-300 inline-block" title="Can only delete within 12 hours">
                            <svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                        </span>
                        @endif
                        @php
                            $formUrl = 'https://docs.google.com/forms/d/e/1FAIpQLScugMTauTcWVP6A7C5THzkVW-vhzcir8QfytkcBGO1az9XBOw/viewform'
                                . '?usp=pp_url'
                                . '&entry.1910425423=' . urlencode('info@m2h.ge')
                                . '&entry.1909848528=' . urlencode($lead->first_name ?? '')
                                . '&entry.2037197163=' . urlencode($lead->last_name ?? '')
                                . '&entry.1332109165=' . urlencode($lead->email ?? '')
                                . '&entry.118955804='  . urlencode($lead->phone ?? '')
                                . '&entry.1056573155=' . urlencode($item->content);
                        @endphp
                        <a href="{{ $formUrl }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 text-xs text-indigo-300 hover:text-indigo-500 transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                            Formu Aç
                        </a>
                        </div>
                    </div>
                </div>

                @elseif($entryType === 'task')
                {{-- Task --}}
                <div class="flex gap-3"
                     x-data="{
                         done: {{ $item->is_done ? 'true' : 'false' }},
                         async toggle() {
                             const r = await fetch('{{ route('leads.tasks.toggle', [$lead, $item]) }}', {
                                 method: 'POST',
                                 headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}
                             });
                             const d = await r.json();
                             this.done = d.is_done;
                         }
                     }">
                    <div class="flex-shrink-0 mt-0.5">
                        <button type="button" @click="toggle()"
                                :class="done
                                    ? 'bg-emerald-500 text-white border-emerald-500'
                                    : 'border-gray-300 bg-white hover:border-indigo-400'"
                                class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all">
                            <svg x-show="done" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span :class="done ? 'line-through text-gray-400' : 'text-gray-800'"
                                  class="text-sm font-medium transition-all">{{ $item->title }}</span>
                            @if($item->due_at)
                            <span class="text-xs {{ $item->due_at->isPast() && !$item->is_done ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                                · due {{ $item->due_at->format('d M Y') }}
                                @if($item->due_at->isPast() && !$item->is_done)
                                <span class="text-red-400">(overdue)</span>
                                @endif
                            </span>
                            @endif
                        </div>
                        @if($item->description)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $item->description }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-0.5">
                            @if($item->createdBy)
                            <span class="text-xs text-gray-400">by {{ $item->createdBy->name }}</span>
                            @endif
                            @if($item->assignedTo)
                            <span class="text-xs text-gray-300">·</span>
                            <span class="text-xs text-gray-400">assigned to {{ $item->assignedTo->name }}</span>
                            @endif
                        </div>
                        @if($item->created_by === auth()->id() || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('leads.tasks.destroy', [$lead, $item]) }}"
                              onsubmit="return confirm('Delete task?')" class="mt-1">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-gray-300 hover:text-red-500 transition-colors">Delete</button>
                        </form>
                        @endif
                    </div>
                </div>

                @elseif($entryType === 'wa_group')
                {{-- WhatsApp message group --}}
                @php $isOutgoing = $entry['direction'] === 'whatsapp_outgoing'; @endphp
                <div class="flex {{ $isOutgoing ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xs lg:max-w-sm {{ $isOutgoing ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200' }} border rounded-xl px-3.5 py-2.5 shadow-sm">
                        <div class="flex items-center gap-1.5 mb-2">
                            <svg class="w-3 h-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.554 4.118 1.522 5.845L.057 23.25l5.565-1.457A11.938 11.938 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75a9.712 9.712 0 0 1-4.95-1.354l-.355-.21-3.305.866.881-3.218-.231-.371A9.712 9.712 0 0 1 2.25 12C2.25 6.615 6.615 2.25 12 2.25S21.75 6.615 21.75 12 17.385 21.75 12 21.75z"/>
                            </svg>
                            <span class="text-xs font-medium {{ $isOutgoing ? 'text-green-700' : 'text-gray-500' }}">
                                {{ $isOutgoing ? ($entry['messages'][0]->user?->name ?? 'Team') : 'WhatsApp' }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            @foreach($entry['messages'] as $msg)
                            <div class="{{ !$loop->last ? 'pb-2 border-b border-gray-100' : '' }}">
                                <p class="text-sm text-gray-800 leading-relaxed">{{ $msg->description }}</p>
                                <div class="flex items-center gap-1 mt-0.5 {{ $isOutgoing ? 'justify-end' : '' }}">
                                    <span class="text-xs text-gray-400">{{ $msg->created_at->diffForHumans() }}</span>
                                    @if($isOutgoing)
                                    @php $waStatus = $msg->meta['status'] ?? null; @endphp
                                    @if($waStatus === 'read')
                                    {{-- Double tick blue (read) --}}
                                    <svg style="width:16px;height:11px;flex-shrink:0" viewBox="0 0 18 12" fill="none">
                                        <path d="M1 6L4.5 9.5L10 3" stroke="#3b82f6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 6L10.5 9.5L17 2" stroke="#3b82f6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    @elseif($waStatus === 'delivered')
                                    {{-- Double tick grey (delivered) --}}
                                    <svg style="width:16px;height:11px;flex-shrink:0" viewBox="0 0 18 12" fill="none">
                                        <path d="M1 6L4.5 9.5L10 3" stroke="#9ca3af" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 6L10.5 9.5L17 2" stroke="#9ca3af" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    @elseif($waStatus === 'sent')
                                    {{-- Single tick grey (sent) --}}
                                    <svg style="width:10px;height:11px;flex-shrink:0" viewBox="0 0 10 12" fill="none">
                                        <path d="M1 6L4 9.5L9 2" stroke="#9ca3af" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    @endif
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @elseif($entryType === 'activity')
                {{-- Activity --}}
                <div class="flex gap-2.5 items-start">
                    <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        @if(in_array($item->type, ['tag_added', 'tag_removed', 'tags_updated']))
                        <svg class="w-2.5 h-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                        </svg>
                        @elseif($item->type === 'custom_field_updated')
                        <svg class="w-2.5 h-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                        </svg>
                        @else
                        <svg class="w-2.5 h-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-600">{{ $item->description }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            @if($item->user)
                            <span class="text-xs text-gray-400">by {{ $item->user->name }}</span>
                            <span class="text-xs text-gray-300">·</span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @endif

                @endforeach
            </div>
            @endif

            {{-- Add Note / Add Task / WhatsApp forms --}}
            <div class="border-t border-gray-100 p-5"
                 x-data="{ formTab: '{{ session('task_success') ? 'task' : (session('wa_success') || session('wa_error') ? 'whatsapp' : 'note') }}' }">
                <div class="flex gap-4 mb-4">
                    <button @click="formTab = 'note'"
                            :class="formTab === 'note'
                                ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600'
                                : 'text-gray-400 hover:text-gray-600'"
                            class="text-xs pb-1 transition-colors">Add Note</button>
                    <button @click="formTab = 'task'"
                            :class="formTab === 'task'
                                ? 'text-indigo-600 font-semibold border-b-2 border-indigo-600'
                                : 'text-gray-400 hover:text-gray-600'"
                            class="text-xs pb-1 transition-colors">Add Task</button>
                    @if($lead->phone)
                    <button @click="formTab = 'whatsapp'"
                            :class="formTab === 'whatsapp'
                                ? 'text-green-600 font-semibold border-b-2 border-green-600'
                                : 'text-gray-400 hover:text-gray-600'"
                            class="text-xs pb-1 transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.554 4.118 1.522 5.845L.057 23.25l5.565-1.457A11.938 11.938 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75a9.712 9.712 0 0 1-4.95-1.354l-.355-.21-3.305.866.881-3.218-.231-.371A9.712 9.712 0 0 1 2.25 12C2.25 6.615 6.615 2.25 12 2.25S21.75 6.615 21.75 12 17.385 21.75 12 21.75z"/>
                        </svg>
                        WhatsApp
                    </button>
                    @endif
                </div>

                {{-- Note form --}}
                <div x-show="formTab === 'note'">
                    <form method="POST" action="{{ route('leads.notes.store', $lead) }}">
                        @csrf
                        <textarea name="content" rows="3" required
                                  placeholder="Write a note…"
                                  class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                         focus:ring-indigo-500 focus:border-indigo-500 resize-none">{{ old('content') }}</textarea>
                        <div class="mt-2"
                             x-data="{
                                sel: ['internal'],
                                toggle(v) {
                                    this.sel = this.sel.filter(x => x !== 'internal');
                                    this.sel.includes(v)
                                        ? (this.sel = this.sel.filter(x => x !== v))
                                        : this.sel.push(v);
                                    if (this.sel.length === 0) this.sel = ['internal'];
                                },
                                has(v) { return this.sel.includes(v); },
                                get val() { return this.sel.join(','); },
                                get label() {
                                    if (this.has('internal')) return 'Internal only';
                                    const map = { service_provider: 'Service Provider', agent: 'Agent', client: 'Client' };
                                    return 'Shared with: ' + this.sel.map(v => map[v]).join(', ');
                                }
                             }">
                            <input type="hidden" name="visibility" :value="val">
                            <div class="flex items-center gap-1.5 mb-2">
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                <span class="text-xs"
                                      :class="has('internal') ? 'text-gray-400' : 'text-emerald-600 font-medium'"
                                      x-text="label"></span>
                            </div>
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @foreach([
                                        ['key' => 'service_provider', 'label' => 'Service Provider'],
                                        ['key' => 'agent',            'label' => 'Agent'],
                                        ['key' => 'client',           'label' => 'Client'],
                                    ] as $opt)
                                    <button type="button" @click="toggle('{{ $opt['key'] }}')"
                                            :class="has('{{ $opt['key'] }}')
                                                ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-400'
                                                : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
                                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full transition-colors font-medium">
                                        <svg x-show="has('{{ $opt['key'] }}')" class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $opt['label'] }}
                                    </button>
                                    @endforeach
                                </div>
                                <button type="submit"
                                        class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5
                                               rounded-lg transition-colors font-medium">
                                    Add Note
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Task form --}}
                <div x-show="formTab === 'task'" x-cloak>
                    <form method="POST" action="{{ route('leads.tasks.store', $lead) }}">
                        @csrf
                        <div class="space-y-3">
                            <input type="text" name="title" required value="{{ old('title') }}"
                                   placeholder="Task title…"
                                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <input type="text" name="description" value="{{ old('description') }}"
                                   placeholder="Description (optional)"
                                   class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <div class="grid grid-cols-2 gap-3">
                                <select name="assigned_to" required
                                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Assign to…</option>
                                    @foreach($internalUsers as $u)
                                    <option value="{{ $u->id }}" {{ old('assigned_to') === $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="datetime-local" name="due_at" required value="{{ old('due_at') }}"
                                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit"
                                    class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5
                                           rounded-lg transition-colors font-medium">
                                Add Task
                            </button>
                        </div>
                    </form>
                </div>

                {{-- WhatsApp send form --}}
                @if($lead->phone)
                <div x-show="formTab === 'whatsapp'" x-cloak
                     x-data="{ waTab: '{{ session('wa_success') || session('wa_error') ? 'text' : 'text' }}' }">

                    @if(session('wa_error'))
                    <div class="mb-3 text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        {{ session('wa_error') }}
                    </div>
                    @endif
                    @if(session('wa_success'))
                    <div class="mb-3 text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        {{ session('wa_success') }}
                    </div>
                    @endif

                    {{-- WA sub-tabs --}}
                    <div class="flex gap-3 mb-3 border-b border-gray-100 pb-2">
                        <button type="button" @click="waTab = 'text'"
                                :class="waTab === 'text' ? 'text-green-700 font-semibold border-b-2 border-green-600' : 'text-gray-400 hover:text-gray-600'"
                                class="text-xs pb-1 -mb-2 transition-colors">Mesaj Yaz</button>
                        @if($waTemplates->isNotEmpty())
                        <button type="button" @click="waTab = 'template'"
                                :class="waTab === 'template' ? 'text-green-700 font-semibold border-b-2 border-green-600' : 'text-gray-400 hover:text-gray-600'"
                                class="text-xs pb-1 -mb-2 transition-colors">Şablon Gönder</button>
                        @endif
                    </div>

                    {{-- Free text --}}
                    <div x-show="waTab === 'text'">
                        <form method="POST" action="{{ route('leads.whatsapp.send', $lead) }}">
                            @csrf
                            <textarea name="message" rows="3" required
                                      placeholder="WhatsApp mesajı yaz…"
                                      class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                             focus:ring-green-500 focus:border-green-500 resize-none">{{ old('message') }}</textarea>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ $lead->phone }}</span>
                                <button type="submit"
                                        class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5
                                               rounded-lg transition-colors font-medium">Gönder</button>
                            </div>
                        </form>
                    </div>

                    {{-- Template send --}}
                    @if($waTemplates->isNotEmpty())
                    <div x-show="waTab === 'template'" x-cloak
                         x-data="{
                             selectedId: '',
                             templates: {{ $waTemplates->map(fn($t) => ['id' => $t->id, 'label' => $t->display_name ?? $t->name, 'preview' => $t->resolveBodyPreview($lead)])->values()->toJson() }},
                             get preview() { const t = this.templates.find(t => t.id == this.selectedId); return t ? t.preview : ''; }
                         }">
                        <form method="POST" action="{{ route('leads.whatsapp.send-template', $lead) }}">
                            @csrf
                            <select name="template_id" x-model="selectedId" required
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:ring-green-500 focus:border-green-500 mb-3">
                                <option value="">— Şablon seçin</option>
                                @foreach($waTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->display_name ?? $tpl->name }}</option>
                                @endforeach
                            </select>

                            {{-- Preview --}}
                            <div x-show="preview" class="mb-3 bg-green-50 border border-green-200 rounded-lg px-3 py-2.5 text-xs text-gray-700 whitespace-pre-wrap leading-relaxed" x-text="preview"></div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">{{ $lead->phone }}</span>
                                <button type="submit" :disabled="!selectedId"
                                        class="text-xs bg-green-600 hover:bg-green-700 disabled:opacity-40 text-white px-3 py-1.5
                                               rounded-lg transition-colors font-medium">Gönder</button>
                            </div>
                        </form>
                    </div>
                    @endif

                </div>
                @endif
            </div>

        </div>
        @endif

    </div>

</div>

@endsection

@push('scripts')
<script>
function customFieldsEditor(initialFields) {
    return {
        editing: false,
        fields: JSON.parse(JSON.stringify(initialFields)),
        _initial: JSON.parse(JSON.stringify(initialFields)),

        toggleMulti(key, value, isExclusive) {
            const field = this.fields[key];
            if (!Array.isArray(field.value)) field.value = [];

            if (isExclusive) {
                const already = field.value.includes(value);
                field.value = already ? [] : [value];
                return;
            }

            const exclusiveVals = field.exclusive_values || [];
            field.value = field.value.filter(v => !exclusiveVals.includes(v));

            const idx = field.value.indexOf(value);
            if (idx >= 0) {
                field.value.splice(idx, 1);
            } else {
                field.value.push(value);
            }
        },

        reset() {
            this.fields = JSON.parse(JSON.stringify(this._initial));
        }
    };
}
</script>
@endpush
