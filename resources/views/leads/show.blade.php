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
<div class="flex items-center gap-3 mb-6">
    <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
        {{ $lead->initials() }}
    </div>
    <div>
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
            @if($lead->meta_ad_name)
            <span title="Ad">{{ $lead->meta_ad_name }}</span>
            @endif
            @if($lead->meta_ad_name && $lead->meta_campaign_name)
            <span class="mx-1 text-gray-300">·</span>
            @endif
            @if($lead->meta_campaign_name)
            <span title="Campaign" class="text-gray-300">{{ $lead->meta_campaign_name }}</span>
            @endif
        </p>
        @endif
    </div>
</div>

<div class="grid grid-cols-3 gap-5">

    {{-- Left: Contact, Deal, Notes/Tasks --}}
    <div class="col-span-2 space-y-5">

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

        {{-- Meta Form Responses --}}
        @if(!empty($lead->meta_form_data))
        @php
        $metaLabels  = config('meta_fields', []);
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
        @php
            $hasAnyValue = $customValuesByKey->isNotEmpty();
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5"
             x-data="customFieldsEditor({{ json_encode(
                $customFields->map(function ($f) use ($customValuesByKey) {
                    $cv = $customValuesByKey[$f->key] ?? null;
                    return [
                        'key'     => $f->key,
                        'type'    => $f->type,
                        'value'   => $f->type === 'multi_select'
                            ? (json_decode($cv?->value ?? '[]', true) ?? [])
                            : ($cv?->value ?? ''),
                        'exclusive_values' => $f->options->where('is_exclusive', true)->pluck('value')->values()->toArray(),
                    ];
                })->keyBy('key')->toJson()
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
                    $cv        = $customValuesByKey[$field->key] ?? null;
                    $rawValue  = $cv?->value;
                    if ($field->type === 'multi_select') {
                        $vals    = json_decode($rawValue ?? '[]', true) ?? [];
                        $display = $field->options->whereIn('value', $vals)->pluck('label')->join(', ');
                    } elseif (in_array($field->type, ['select'])) {
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
                    {{-- Hidden inputs for multi_select --}}
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

        {{-- Tags --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Tags</h3>
            @if($allTags->isEmpty())
            <p class="text-sm text-gray-400">No tags yet.
                @if(auth()->user()->isAdmin())
                <a href="{{ route('settings.tags.create') }}" class="text-indigo-600 hover:text-indigo-800 ml-1">Create tags →</a>
                @endif
            </p>
            @else
            @php
                $tagsByGroup = $allTags->groupBy(fn ($t) => $t->group?->name ?? '');
                $grouped     = $tagsByGroup->filter(fn ($v, $k) => $k !== '')->sortKeys();
                $ungrouped   = $tagsByGroup->get('', collect());
            @endphp

            @foreach($grouped as $groupName => $tags)
            <div class="mb-4 last:mb-0">
                <p class="text-xs text-gray-400 font-medium mb-2">{{ $groupName }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                    @php $isActive = $lead->tags->contains('id', $tag->id); @endphp
                    <button type="button"
                            x-data="{
                                active: {{ $isActive ? 'true' : 'false' }},
                                async toggle() {
                                    const r = await fetch('{{ route('leads.tags.toggle', [$lead, $tag]) }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                    });
                                    const d = await r.json();
                                    this.active = d.active;
                                }
                            }"
                            @click="toggle()"
                            class="px-3 py-1 rounded-full text-xs font-medium border transition-all"
                            :class="active ? 'text-white border-transparent' : 'text-gray-500 border-gray-200 bg-white hover:border-gray-400'"
                            :style="active ? 'background-color: {{ $tag->color }}; border-color: {{ $tag->color }}' : ''">
                        {{ $tag->name }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endforeach

            @if($ungrouped->isNotEmpty())
            <div class="{{ $grouped->isNotEmpty() ? 'mt-4 pt-4 border-t border-gray-100' : '' }}">
                @if($grouped->isNotEmpty())
                <p class="text-xs text-gray-400 font-medium mb-2">Other</p>
                @endif
                <div class="flex flex-wrap gap-2">
                    @foreach($ungrouped as $tag)
                    @php $isActive = $lead->tags->contains('id', $tag->id); @endphp
                    <button type="button"
                            x-data="{
                                active: {{ $isActive ? 'true' : 'false' }},
                                async toggle() {
                                    const r = await fetch('{{ route('leads.tags.toggle', [$lead, $tag]) }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                    });
                                    const d = await r.json();
                                    this.active = d.active;
                                }
                            }"
                            @click="toggle()"
                            class="px-3 py-1 rounded-full text-xs font-medium border transition-all"
                            :class="active ? 'text-white border-transparent' : 'text-gray-500 border-gray-200 bg-white hover:border-gray-400'"
                            :style="active ? 'background-color: {{ $tag->color }}; border-color: {{ $tag->color }}' : ''">
                        {{ $tag->name }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            @endif
        </div>

        {{-- Programs --}}
        @php $sortedPrograms = $lead->programs->sortByDesc('pivot.is_primary'); @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Programs</h3>

            @forelse($sortedPrograms as $program)
            <div class="flex items-center gap-3 py-2.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">

                {{-- Primary star --}}
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
                    <p class="text-sm font-medium text-gray-800">{{ $program->name }}</p>
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
                        class="flex-shrink-0 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors font-medium">
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

        {{-- Notes & Tasks tabs --}}
        @php
            $openTasks   = $lead->tasks->where('is_done', false)->sortBy('due_at');
            $doneTasks   = $lead->tasks->where('is_done', true)->sortByDesc('due_at');
            $allTasks    = $openTasks->merge($doneTasks);
            $overdueTasks = $openTasks->filter(fn ($t) => $t->due_at && $t->due_at->isPast());
            $defaultTab  = session('task_success') || $overdueTasks->isNotEmpty() ? 'tasks' : 'notes';
        @endphp

        @if($overdueTasks->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-0 flex items-start gap-2.5">
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

        <div class="bg-white rounded-xl border border-gray-200"
             x-data="{ tab: '{{ $defaultTab }}' }">

            {{-- Tab headers --}}
            <div class="flex border-b border-gray-200 px-5">
                <button @click="tab = 'notes'"
                        :class="tab === 'notes' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-1 py-3.5 text-sm font-medium border-b-2 mr-6 transition-colors">
                    Notes
                    <span class="ml-1.5 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">
                        {{ $lead->notes->count() }}
                    </span>
                </button>
                <button @click="tab = 'tasks'"
                        :class="tab === 'tasks' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-1 py-3.5 text-sm font-medium border-b-2 transition-colors">
                    Tasks
                    @if($openTasks->count() > 0)
                    <span class="ml-1.5 text-xs bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full font-semibold">
                        {{ $openTasks->count() }}
                    </span>
                    @else
                    <span class="ml-1.5 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">
                        {{ $lead->tasks->count() }}
                    </span>
                    @endif
                </button>
            </div>

            {{-- NOTES --}}
            <div x-show="tab === 'notes'" id="notes" class="p-5">

                {{-- Add note --}}
                <form method="POST" action="{{ route('leads.notes.store', $lead) }}" class="mb-6">
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

                        {{-- Visibility label --}}
                        <div class="flex items-center gap-1.5 mb-2">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <span class="text-xs" :class="has('internal') ? 'text-gray-400' : 'text-emerald-600 font-medium'" x-text="label"></span>
                        </div>

                        {{-- Chips + submit --}}
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @foreach([
                                    ['key' => 'service_provider', 'label' => 'Service Provider'],
                                    ['key' => 'agent',            'label' => 'Agent'],
                                    ['key' => 'client',          'label' => 'Client'],
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

                {{-- Notes list --}}
                @forelse($lead->notes as $note)
                <div class="pb-4 {{ !$loop->last ? 'border-b border-gray-100 mb-4' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center
                                        text-white text-xs font-semibold flex-shrink-0">
                                {{ strtoupper(substr($note->createdBy->name, 0, 1)) }}
                            </div>
                            <span class="text-xs font-medium text-gray-700">{{ $note->createdBy->name }}</span>
                            <span class="text-xs text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                            @php $visParts = explode(',', $note->visibility ?? 'internal'); @endphp
                        </div>
                        @php
                            $canDelete = auth()->user()->isAdmin()
                                || ($note->created_by === auth()->id() && $note->created_at->diffInHours(now()) < 12);
                        @endphp
                        @if($canDelete)
                        <form method="POST" action="{{ route('leads.notes.destroy', [$lead, $note]) }}"
                              onsubmit="return confirm('Delete this note?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete note"
                                    class="text-gray-300 hover:text-red-500 transition-colors flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                        @elseif($note->created_by === auth()->id())
                        <span class="text-xs text-gray-300 flex-shrink-0" title="Can only delete within 12 hours">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-700 mt-2.5 whitespace-pre-wrap leading-relaxed">{{ $note->content }}</p>
                    {{-- Visibility indicator --}}
                    <div class="flex items-center gap-1.5 mt-2">
                        <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        @if(in_array('internal', $visParts))
                            <span class="text-xs text-gray-300">Internal only</span>
                        @else
                            <span class="text-xs text-emerald-600">
                                Shared with:
                                @php
                                    $visLabels = array_filter([
                                        in_array('service_provider', $visParts) ? 'Service Provider' : null,
                                        in_array('agent', $visParts)            ? 'Agent'            : null,
                                        in_array('client', $visParts)           ? 'Client'           : null,
                                    ]);
                                @endphp
                                {{ implode(', ', $visLabels) }}
                            </span>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">No notes yet. Add one above.</p>
                @endforelse
            </div>

            {{-- TASKS --}}
            <div x-show="tab === 'tasks'" id="tasks" class="p-5">

                {{-- Add task --}}
                <form method="POST" action="{{ route('leads.tasks.store', $lead) }}" class="mb-6 space-y-2.5">
                    @csrf
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Task title…"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                    <div class="grid grid-cols-2 gap-2.5">
                        <select name="assigned_to" required
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                       focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Assign to…</option>
                            @foreach($internalUsers as $user)
                            <option value="{{ $user->id }}"
                                    {{ old('assigned_to', auth()->user()?->id) === $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                            @endforeach
                        </select>
                        <input type="datetime-local" name="due_at" value="{{ old('due_at') }}" required
                               class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5
                                       rounded-lg transition-colors font-medium">
                            Add Task
                        </button>
                    </div>
                </form>

                {{-- Tasks list --}}
                @forelse($allTasks as $task)
                <div x-data="{
                         done: {{ $task->is_done ? 'true' : 'false' }},
                         async toggle() {
                             const r = await fetch('{{ route('leads.tasks.toggle', [$lead, $task]) }}', {
                                 method: 'PATCH',
                                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                             });
                             const d = await r.json();
                             this.done = d.is_done;
                         }
                     }"
                     class="flex items-start gap-3 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">

                    {{-- Checkbox --}}
                    <button @click="toggle()" type="button" class="flex-shrink-0 mt-0.5">
                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                             :class="done ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 hover:border-indigo-400'">
                            <template x-if="done">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            </template>
                        </div>
                    </button>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 transition-colors"
                           :class="done ? 'line-through text-gray-400' : ''">
                            {{ $task->title }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                            <span class="text-xs text-gray-400">{{ $task->assignedTo->name }}</span>
                            <span class="text-xs {{ $task->isOverdue() ? 'text-red-500 font-medium' : 'text-gray-400' }}"
                                  :class="done ? 'text-gray-400 !font-normal line-through' : ''">
                                {{ $task->due_at->format('d M Y, H:i') }}
                                @if($task->isOverdue())
                                <span x-show="!done" class="ml-1 text-red-500">· Overdue</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Delete --}}
                    @if($task->created_by === auth()->id() || auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('leads.tasks.destroy', [$lead, $task]) }}"
                          onsubmit="return confirm('Delete this task?')">
                        @csrf @method('DELETE')
                        <button type="submit" title="Delete task"
                                class="text-gray-300 hover:text-red-500 transition-colors mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">No tasks yet. Add one above.</p>
                @endforelse
            </div>

        </div>{{-- end tabs --}}
    </div>

    {{-- Right: Assignment + Companies + Stage history --}}
    <div class="space-y-5">

        {{-- Assignee --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5"
             x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Assignee</h3>
                @if($internalUsers->isNotEmpty())
                <button @click="editing = !editing" type="button"
                        class="text-xs text-indigo-600 hover:text-indigo-800"
                        x-text="editing ? 'Cancel' : 'Change'"></button>
                @endif
            </div>

            {{-- Display --}}
            <div x-show="!editing">
                @if($lead->assignedTo)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center
                                text-white text-sm font-semibold flex-shrink-0">
                        {{ strtoupper(substr($lead->assignedTo->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $lead->assignedTo->name }}</p>
                        <p class="text-xs text-gray-400">{{ $lead->assignedTo->roleLabel() }}</p>
                    </div>
                </div>
                @else
                <p class="text-sm text-gray-400">Unassigned</p>
                @endif
            </div>

            {{-- Edit form --}}
            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('leads.assign-user', $lead) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="assigned_to"
                            class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Unassign —</option>
                        @foreach($internalUsers as $u)
                        <option value="{{ $u->id }}" {{ $lead->assigned_to === $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="flex-shrink-0 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                        Save
                    </button>
                </form>
            </div>
        </div>

        {{-- Service Provider --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5"
             x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Service Provider</h3>
                @if($serviceProviders->isNotEmpty())
                <button @click="editing = !editing" type="button"
                        class="text-xs text-indigo-600 hover:text-indigo-800"
                        x-text="editing ? 'Cancel' : 'Change'"></button>
                @endif
            </div>

            <div x-show="!editing">
                @if($lead->serviceProvider)
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-400 flex-shrink-0"></span>
                    <p class="text-sm font-medium text-gray-800">{{ $lead->serviceProvider->name }}</p>
                </div>
                @else
                <p class="text-sm text-gray-400">Not assigned</p>
                @endif
            </div>

            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('leads.assign-company', $lead) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="field" value="service_provider_id">
                    <select name="company_id"
                            class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— None —</option>
                        @foreach($serviceProviders as $sp)
                        <option value="{{ $sp->id }}" {{ $lead->service_provider_id === $sp->id ? 'selected' : '' }}>
                            {{ $sp->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="flex-shrink-0 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                        Save
                    </button>
                </form>
            </div>
        </div>

        {{-- Agent --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5"
             x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Agent</h3>
                @if($agents->isNotEmpty())
                <button @click="editing = !editing" type="button"
                        class="text-xs text-indigo-600 hover:text-indigo-800"
                        x-text="editing ? 'Cancel' : 'Change'"></button>
                @endif
            </div>

            <div x-show="!editing">
                @if($lead->agent)
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-gray-400 flex-shrink-0"></span>
                    <p class="text-sm font-medium text-gray-800">{{ $lead->agent->name }}</p>
                </div>
                @else
                <p class="text-sm text-gray-400">Not assigned</p>
                @endif
            </div>

            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('leads.assign-company', $lead) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="field" value="agent_id">
                    <select name="company_id"
                            class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— None —</option>
                        @foreach($agents as $ag)
                        <option value="{{ $ag->id }}" {{ $lead->agent_id === $ag->id ? 'selected' : '' }}>
                            {{ $ag->name }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="flex-shrink-0 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                        Save
                    </button>
                </form>
            </div>
        </div>

        {{-- Stage history --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Stage History</h3>
            @forelse($lead->statusHistory as $entry)
            <div class="flex gap-3 pb-4 {{ !$loop->last ? 'border-b border-gray-100 mb-4' : '' }}">
                <div class="w-1.5 h-1.5 rounded-full bg-indigo-400 flex-shrink-0 mt-1.5"></div>
                <div class="flex-1 min-w-0">
                    @if($entry->fromStage)
                    <p class="text-xs text-gray-700">
                        <span class="text-gray-400">{{ $entry->fromStage->name }}</span>
                        <span class="mx-1 text-gray-300">→</span>
                        <span class="font-medium">{{ $entry->toStage?->name }}</span>
                    </p>
                    @else
                    <p class="text-xs text-gray-700">
                        Added to <span class="font-medium">{{ $entry->toStage?->name }}</span>
                    </p>
                    @endif
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $entry->changedBy?->name ?? 'System' }}
                        · {{ $entry->changed_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400">No history yet.</p>
            @endforelse
        </div>

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
                // Exclusive option: if already selected, deselect; otherwise select alone
                const already = field.value.includes(value);
                field.value = already ? [] : [value];
                return;
            }

            // Remove any exclusive options when selecting a non-exclusive one
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
