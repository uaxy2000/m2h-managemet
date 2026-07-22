@extends('layouts.app')

@section('title', 'Custom Fields')
@section('heading', 'Settings')

@section('content')
@include('settings._nav')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-3 gap-6">

    {{-- Left: Add new field --}}
    <div class="col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Add New Field</h3>
            <form method="POST" action="{{ route('settings.custom-fields.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Key <span class="text-gray-400">(snake_case, unique)</span></label>
                    <input type="text" name="key" value="{{ old('key') }}" required maxlength="80"
                           placeholder="e.g. budget_range"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('key')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Display Label</label>
                    <input type="text" name="label" value="{{ old('label') }}" required maxlength="100"
                           placeholder="e.g. Budget Range"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('label')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select name="type" required
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="text"         {{ old('type') === 'text'         ? 'selected' : '' }}>Text</option>
                        <option value="date"         {{ old('type') === 'date'         ? 'selected' : '' }}>Date</option>
                        <option value="select"       {{ old('type') === 'select'       ? 'selected' : '' }}>Select (single)</option>
                        <option value="multi_select" {{ old('type') === 'multi_select' ? 'selected' : '' }}>Select (multi)</option>
                    </select>
                </div>
                <button type="submit"
                        class="w-full px-3 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                    Create Field
                </button>
            </form>
        </div>
    </div>

    {{-- Right: Field list --}}
    <div class="col-span-2">
        <p class="text-sm text-gray-500 mb-4">
            Custom fields capture structured data per lead. Select/multi-select fields need options. Meta form questions can be mapped to automatically populate values on import.
        </p>

        @if($fields->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
            <p class="text-gray-400 text-sm">No custom fields yet.</p>
        </div>
        @else

        <div class="space-y-4">
        @foreach($fields as $field)
        <div x-data="{ open: {{ session('open_field') === $field->id ? 'true' : 'false' }}, editing: false, editingOption: null }"
             class="bg-white rounded-xl border border-gray-200 overflow-hidden">

            {{-- Field header --}}
            <div class="flex items-center gap-3 px-5 py-3.5">
                <button type="button" @click="open = !open"
                        class="flex items-center gap-2 flex-1 min-w-0 text-left">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="open ? 'rotate-90' : ''"
                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-800 truncate">{{ $field->label }}</span>
                    <span class="text-xs text-gray-400 font-mono bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded flex-shrink-0">{{ $field->key }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0
                        @if($field->type === 'date')         bg-blue-50 text-blue-600
                        @elseif($field->type === 'text')     bg-gray-100 text-gray-600
                        @elseif($field->type === 'select')   bg-purple-50 text-purple-600
                        @else                                bg-indigo-50 text-indigo-600
                        @endif">
                        {{ str_replace('_', ' ', $field->type) }}
                    </span>
                    @if(!$field->is_active)
                    <span class="text-xs bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full flex-shrink-0">inactive</span>
                    @endif
                </button>

                {{-- Edit label inline --}}
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <button type="button" @click.stop="editing = !editing"
                            class="p-1.5 text-gray-400 hover:text-indigo-500 rounded transition-colors" title="Edit">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                        </svg>
                    </button>
                    <form method="POST" action="{{ route('settings.custom-fields.destroy', $field) }}"
                          onsubmit="return confirm('Delete field \'{{ addslashes($field->label) }}\'? All stored values will be lost.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Edit field label/active --}}
            <div x-show="editing" x-cloak class="px-5 pb-4 border-t border-gray-100 pt-3">
                <form method="POST" action="{{ route('settings.custom-fields.update', $field) }}"
                      class="flex items-end gap-3">
                    @csrf @method('PUT')
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Display Label</label>
                        <input type="text" name="label" value="{{ $field->label }}" required maxlength="100"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sort</label>
                        <input type="number" name="sort_order" value="{{ $field->sort_order }}" min="0"
                               class="w-20 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="flex items-center gap-2 pb-0.5">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="active_{{ $field->id }}" value="1"
                               {{ $field->is_active ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="active_{{ $field->id }}" class="text-xs text-gray-600">Active</label>
                    </div>
                    <button type="submit"
                            class="px-3 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                        Save
                    </button>
                    <button type="button" @click="editing = false"
                            class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </form>
            </div>

            {{-- Expanded body --}}
            <div x-show="open" x-cloak class="border-t border-gray-100">

                @if($field->isSelectType())
                {{-- Options list --}}
                <div class="px-5 py-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Options</h4>

                    @if($field->options->isEmpty())
                    <p class="text-sm text-gray-400 mb-3">No options yet.</p>
                    @else
                    <div class="space-y-2 mb-4">
                    @foreach($field->options as $opt)
                    <div x-data="{ editOpt: false }" class="border border-gray-100 rounded-lg">
                        {{-- View mode --}}
                        <div x-show="!editOpt" class="flex items-center gap-3 px-3 py-2">
                            <span class="text-xs font-mono text-gray-400 w-24 truncate flex-shrink-0">{{ $opt->value }}</span>
                            <span class="text-sm text-gray-800 flex-1">{{ $opt->label }}</span>
                            @if($opt->is_exclusive)
                            <span class="text-xs bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded">exclusive</span>
                            @endif
                            @if(!empty($opt->meta_aliases))
                            <span class="text-xs bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded" title="{{ implode(', ', $opt->meta_aliases) }}">
                                {{ count($opt->meta_aliases) }} alias{{ count($opt->meta_aliases) > 1 ? 'es' : '' }}
                            </span>
                            @endif
                            <button type="button" @click="editOpt = true"
                                    class="p-1 text-gray-400 hover:text-indigo-500 rounded transition-colors flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('settings.custom-fields.options.destroy', [$field, $opt]) }}"
                                  onsubmit="return confirm('Delete option \'{{ addslashes($opt->label) }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1 text-gray-400 hover:text-red-500 rounded transition-colors flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        {{-- Edit mode --}}
                        <div x-show="editOpt" x-cloak class="px-3 py-3">
                            <form method="POST" action="{{ route('settings.custom-fields.options.update', [$field, $opt]) }}"
                                  class="space-y-2">
                                @csrf @method('PUT')
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Label</label>
                                        <input type="text" name="label" value="{{ $opt->label }}" required maxlength="150"
                                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div class="flex items-center gap-2 pb-1">
                                        <input type="hidden" name="is_exclusive" value="0">
                                        <input type="checkbox" name="is_exclusive" value="1"
                                               {{ $opt->is_exclusive ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <label class="text-xs text-gray-600 whitespace-nowrap">Exclusive</label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">
                                        Meta aliases
                                        <span class="font-normal text-gray-400">(one per line — raw Meta answer values that map to this option)</span>
                                    </label>
                                    <textarea name="meta_aliases" rows="3" placeholder="e.g. Nakit olarak 3 ila 6 ay içinde uygunum"
                                              class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none font-mono">{{ !empty($opt->meta_aliases) ? implode("\n", $opt->meta_aliases) : '' }}</textarea>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                                        Save
                                    </button>
                                    <button type="button" @click="editOpt = false"
                                            class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                    </div>
                    @endif

                    {{-- Add option --}}
                    <form method="POST" action="{{ route('settings.custom-fields.options.store', $field) }}"
                          class="flex items-end gap-2 border-t border-gray-100 pt-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Value key</label>
                            <input type="text" name="value" required maxlength="100" placeholder="e.g. investment"
                                   class="w-36 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">Display label</label>
                            <input type="text" name="label" required maxlength="150" placeholder="e.g. Investment"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <button type="submit"
                                class="px-3 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors flex-shrink-0">
                            Add Option
                        </button>
                    </form>
                </div>
                @endif

                {{-- Meta question mappings --}}
                <div class="px-5 py-4 {{ $field->isSelectType() ? 'border-t border-gray-100' : '' }}">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Meta Form Question Mapping</h4>
                    <p class="text-xs text-gray-400 mb-3">When a Meta lead arrives with this exact question, the answer will be auto-mapped to this field.</p>

                    @if($field->metaQuestionMappings->isEmpty())
                    <p class="text-sm text-gray-400 mb-3">No Meta question mapped yet.</p>
                    @else
                    <div class="space-y-1.5 mb-3">
                    @foreach($field->metaQuestionMappings as $mapping)
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono text-gray-600 bg-gray-50 border border-gray-200 rounded px-2 py-1 flex-1 truncate">{{ $mapping->meta_question_key }}</span>
                        <form method="POST" action="{{ route('settings.custom-fields.mappings.destroy', $mapping) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1 text-gray-400 hover:text-red-500 rounded transition-colors flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ route('settings.custom-fields.mappings.store', $field) }}"
                          class="flex gap-2">
                        @csrf
                        <input type="text" name="meta_question_key" required maxlength="500"
                               placeholder="Paste the raw Meta form question text…"
                               class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit"
                                class="px-3 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors flex-shrink-0">
                            Map
                        </button>
                    </form>
                </div>

            </div>
        </div>
        @endforeach
        </div>

        @endif
    </div>

</div>
@endsection
