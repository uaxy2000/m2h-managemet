@extends('layouts.app')

@section('title', 'Leads')
@section('heading', 'Leads')
@section('main-class', 'flex flex-col overflow-hidden')

@section('content')
<div class="flex flex-col h-full">

    {{-- Top bar --}}
    <div class="flex items-center border-b border-gray-200 bg-white flex-shrink-0 px-6">
        <div class="flex -mb-px overflow-x-auto">
            @forelse($pipelines as $pipeline)
            <a href="{{ route('leads.index', ['pipeline' => $pipeline->id]) }}"
               class="flex-shrink-0 px-4 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                      {{ ($currentPipeline?->id === $pipeline->id)
                          ? 'border-indigo-600 text-indigo-600'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $pipeline->name }}
            </a>
            @empty
            <span class="px-4 py-3.5 text-sm text-gray-400">No pipelines</span>
            @endforelse
        </div>
        <div class="ml-auto flex-shrink-0 pl-4 py-2.5">
            <a href="{{ route('leads.create', ['pipeline' => $currentPipeline?->id]) }}"
               class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3.5 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Lead
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    @php
    $allTagsFlat      = $tagGroups->flatMap(fn ($g) => $g->tags)->concat($ungroupedTags);
    $selectedTagsJson = json_encode(
        $allTagsFlat->whereIn('id', $filters['tags'])
            ->map(fn ($t) => ['id' => $t->id, 'color' => $t->color, 'name' => $t->name])
            ->values()->toArray(),
        JSON_HEX_QUOT | JSON_HEX_TAG
    );
    @endphp
    <form method="GET" action="{{ route('leads.index') }}" id="filter-form"
          class="flex-shrink-0 flex flex-wrap items-center gap-2 px-6 py-2.5 bg-white border-b border-gray-200">
        <input type="hidden" name="pipeline" value="{{ $currentPipeline?->id }}">

        {{-- Name search --}}
        <div class="relative flex-shrink-0">
            <svg class="w-3.5 h-3.5 absolute left-2.5 top-2 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search name…"
                   class="pl-8 pr-3 py-1.5 rounded-lg border border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 w-40">
        </div>

        {{-- Assigned to (admin/super_admin only) --}}
        @if(auth()->user()->role !== 'user')
        <select name="assigned_to"
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All users</option>
            @foreach($internalUsers as $u)
            <option value="{{ $u->id }}" @selected($filters['assigned_to'] == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @else
        <span class="text-xs text-gray-400 italic flex-shrink-0">Your leads only</span>
        @endif

        {{-- Source --}}
        <select name="source"
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All sources</option>
            <option value="meta_ad" @selected($filters['source'] === 'meta_ad')>Meta Ad</option>
            <option value="manual"  @selected($filters['source'] === 'manual')>Manual</option>
            <option value="agent"   @selected($filters['source'] === 'agent')>Agent</option>
        </select>

        {{-- Program --}}
        @if($programsByCountry->isNotEmpty())
        <select name="program_id"
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All programs</option>
            @foreach($programsByCountry as $country => $countryPrograms)
            <optgroup label="{{ $country }}">
                <option value="country:{{ $country }}" @selected($filters['program_id'] === 'country:'.$country)>— All {{ $country }}</option>
                @foreach($countryPrograms as $prog)
                <option value="{{ $prog->id }}" @selected($filters['program_id'] === $prog->id)>{{ $prog->name }}</option>
                @endforeach
            </optgroup>
            @endforeach
        </select>
        @endif

        {{-- Tags popup (multi-select) --}}
        @if($hasTags)
        <div x-data="{
                open: false,
                selected: {{ $selectedTagsJson }},
                toggle(id, color, name) {
                    const i = this.selected.findIndex(t => t.id === id);
                    i >= 0 ? this.selected.splice(i, 1) : this.selected.push({ id, color, name });
                },
                has(id) { return this.selected.some(t => t.id === id); }
             }"
             class="relative flex-shrink-0"
             @click.outside="open = false">

            {{-- Hidden inputs — one per selected tag --}}
            <template x-for="t in selected" :key="t.id">
                <input type="hidden" name="tags[]" :value="t.id">
            </template>

            {{-- Trigger button --}}
            <button type="button" @click="open = !open"
                    :class="selected.length ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border transition-colors">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                </svg>
                Tags
                <span x-show="selected.length > 0" class="flex items-center gap-1 ml-0.5">
                    <span class="text-xs font-semibold bg-indigo-200 text-indigo-800 rounded-full px-1.5 py-0.5 leading-none"
                          x-text="selected.length"></span>
                    <span @click.stop="selected = []"
                          class="text-indigo-400 hover:text-red-500 transition-colors leading-none cursor-pointer">×</span>
                </span>
                <svg class="w-3 h-3 ml-1 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>

            {{-- Dropdown panel --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute left-0 top-full mt-1 w-60 bg-white rounded-xl border border-gray-200 shadow-lg z-50 max-h-80 overflow-y-auto py-1">

                <button type="button" @click="selected = []; open = false"
                        class="w-full text-left px-3 py-1.5 text-xs text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors">
                    — Clear tag filter
                </button>
                <div class="border-t border-gray-100 my-1"></div>

                @foreach($tagGroups as $group)
                @if($group->tags->isNotEmpty())
                <div class="pb-1">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-3 pt-2 pb-1">{{ $group->name }}</p>
                    @foreach($group->tags as $tag)
                    <button type="button"
                            @click="toggle('{{ $tag->id }}', '{{ $tag->color }}', '{{ addslashes($tag->name) }}')"
                            :class="has('{{ $tag->id }}') ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-left transition-colors">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color:{{ $tag->color }}"></span>
                        <span :class="has('{{ $tag->id }}') ? 'text-indigo-700 font-medium' : 'text-gray-700'">{{ $tag->name }}</span>
                        <svg x-show="has('{{ $tag->id }}')" class="w-3.5 h-3.5 ml-auto text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    @endforeach
                </div>
                @endif
                @endforeach

                @if($ungroupedTags->isNotEmpty())
                <div class="pb-1">
                    @if($tagGroups->contains(fn ($g) => $g->tags->isNotEmpty()))
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-3 pt-2 pb-1">Other</p>
                    @endif
                    @foreach($ungroupedTags as $tag)
                    <button type="button"
                            @click="toggle('{{ $tag->id }}', '{{ $tag->color }}', '{{ addslashes($tag->name) }}')"
                            :class="has('{{ $tag->id }}') ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-left transition-colors">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color:{{ $tag->color }}"></span>
                        <span :class="has('{{ $tag->id }}') ? 'text-indigo-700 font-medium' : 'text-gray-700'">{{ $tag->name }}</span>
                        <svg x-show="has('{{ $tag->id }}')" class="w-3.5 h-3.5 ml-auto text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Duplicate toggle --}}
        <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer select-none flex-shrink-0">
            <input type="checkbox" name="duplicate" value="1" @checked($filters['duplicate'])
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Duplicate
        </label>

        {{-- Submit --}}
        <button type="submit"
                class="flex-shrink-0 px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
            Search
        </button>

        {{-- Active filter count + clear all --}}
        @php
        $activeCount = collect([
            $filters['search'] ?: null,
            (auth()->user()->role !== 'user' && $filters['assigned_to']) ? $filters['assigned_to'] : null,
            $filters['source'] ?: null,
            $filters['program_id'] ?: null,
            $filters['duplicate'] ? 1 : null,
            count($filters['tags']) > 0 ? 1 : null,
        ])->filter()->count();
        @endphp
        @if($activeCount > 0)
        <span class="flex-shrink-0 text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">
            {{ $activeCount }} active
        </span>
        <a href="{{ route('leads.index', ['pipeline' => $currentPipeline?->id]) }}"
           class="flex-shrink-0 text-xs text-gray-400 hover:text-red-500 transition-colors">
            × Clear
        </a>
        @endif
    </form>

    @if(session('success'))
    <div class="flex-shrink-0 mx-6 mt-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-2.5 text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if(session('warning'))
    <div class="flex-shrink-0 mx-6 mt-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-2.5 text-sm">
        {{ session('warning') }}
    </div>
    @endif

    @if(!$currentPipeline)
    <div class="flex-1 flex items-center justify-center flex-col gap-3 text-center">
        <p class="text-gray-400 text-sm">No active pipeline found.</p>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('settings.pipelines.create') }}"
           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            Create a pipeline →
        </a>
        @endif
    </div>
    @else

    {{-- Kanban board --}}
    <div class="flex-1 overflow-x-auto overflow-y-hidden cursor-grab" id="kanban-board">
        <div class="flex gap-4 h-full px-6 py-5">

            @forelse($currentPipeline->stages as $stage)
            <div class="flex flex-col w-72 flex-shrink-0 h-full">

                {{-- Column header --}}
                <div class="flex items-center gap-2 mb-3 px-1">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                         style="background-color: {{ $stage->color }}"></div>
                    <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider truncate">
                        {{ $stage->name }}
                    </span>
                    <span class="ml-auto text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full stage-count flex-shrink-0">
                        {{ $stage->leads->count() }}
                    </span>
                </div>

                {{-- Cards --}}
                <div class="stage-column flex-1 overflow-y-auto space-y-2.5 pb-2 min-h-16 rounded-lg transition-colors"
                     data-stage="{{ $stage->id }}">
                    @foreach($stage->leads as $lead)
                    <div class="lead-card bg-white rounded-xl border border-gray-200 p-3.5 shadow-sm
                                hover:shadow-md hover:border-gray-300 transition-all cursor-pointer select-none"
                         data-id="{{ $lead->id }}"
                         data-href="{{ route('leads.show', $lead) }}">

                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $lead->fullName() }}</p>

                        @if($lead->email)
                        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $lead->email }}</p>
                        @endif
                        @if($lead->phone)
                        <p class="text-xs text-gray-400">{{ $lead->phone }}</p>
                        @endif

                        @if($lead->subStage)
                        <div class="mt-2">
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                                {{ $lead->subStage->name }}
                            </span>
                        </div>
                        @endif

                        <div class="flex items-center gap-2 mt-2.5">
                            @if($lead->country_of_origin)
                            <span class="text-xs text-gray-400 truncate">{{ $lead->country_of_origin }}</span>
                            @endif
                            @if($lead->potential_value)
                            <span class="text-xs font-semibold text-emerald-600 ml-auto">
                                ${{ number_format((float) $lead->potential_value) }}
                            </span>
                            @endif
                            @if($lead->assignedTo)
                            <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center
                                        text-white text-xs font-semibold flex-shrink-0
                                        {{ ($lead->potential_value || $lead->country_of_origin) ? '' : 'ml-auto' }}"
                                 title="{{ $lead->assignedTo->name }}">
                                {{ strtoupper(substr($lead->assignedTo->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>

                        @php $primaryProgram = $lead->programs->first(); @endphp
                        @if($primaryProgram)
                        <div class="mt-2 flex items-center gap-1 text-xs text-purple-600">
                            <svg class="w-3 h-3 flex-shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                            </svg>
                            <span class="truncate">{{ $primaryProgram->country }} — {{ $primaryProgram->name }}</span>
                        </div>
                        @endif

                        @if($lead->tags->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($lead->tags as $t)
                            <span class="inline-block w-2 h-2 rounded-full flex-shrink-0"
                                  style="background-color:{{ $t->color }}"
                                  title="{{ $t->name }}"></span>
                            @endforeach
                        </div>
                        @endif

                        @if($lead->meta_platform || $lead->is_duplicate_flag)
                        <div class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                            @if($lead->meta_platform === 'ig')
                            <span class="inline-flex items-center gap-1 text-xs bg-pink-50 text-pink-600 px-1.5 py-0.5 rounded-full font-medium">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                Instagram
                            </span>
                            @elseif($lead->meta_platform === 'fb')
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full font-medium">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </span>
                            @endif
                            @if($lead->is_duplicate_flag)
                            <span class="inline-flex items-center gap-1 text-xs text-amber-600 font-medium">
                                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                </svg>
                                Possible duplicate
                            </span>
                            @endif
                        </div>
                        @endif

                    </div>
                    @endforeach
                </div>

                {{-- Add to stage --}}
                <a href="{{ route('leads.create', ['pipeline' => $currentPipeline->id, 'stage' => $stage->id]) }}"
                   class="mt-2 flex items-center gap-1.5 text-xs text-gray-400 hover:text-indigo-600
                          transition-colors px-1 py-1.5 rounded">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add lead
                </a>

            </div>
            @empty
            <div class="flex items-center justify-center text-gray-400 text-sm w-full">
                No stages in this pipeline.
                @if(auth()->user()->isAdmin())
                <a href="{{ route('settings.pipelines.edit', $currentPipeline) }}"
                   class="text-indigo-600 hover:text-indigo-800 ml-1">Add stages →</a>
                @endif
            </div>
            @endforelse

            {{-- Right padding for scroll --}}
            <div class="w-2 flex-shrink-0"></div>
        </div>
    </div>

    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf   = document.querySelector('meta[name="csrf-token"]').content;
    let lastDrag = 0;

    document.querySelectorAll('.stage-column').forEach(col => {
        Sortable.create(col, {
            group: 'leads',
            animation: 150,
            ghostClass: 'opacity-40',
            dragClass: 'shadow-xl',
            onEnd(evt) {
                lastDrag = Date.now();
                if (evt.from === evt.to) return;

                const leadId  = evt.item.dataset.id;
                const stageId = evt.to.dataset.stage;

                fetch(`/leads/${leadId}/move`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ stage_id: stageId }),
                }).catch(console.error);

                // Refresh count badges
                const fromBadge = evt.from.closest('.flex.flex-col').querySelector('.stage-count');
                const toBadge   = evt.to.closest('.flex.flex-col').querySelector('.stage-count');
                if (fromBadge) fromBadge.textContent = evt.from.querySelectorAll('.lead-card').length;
                if (toBadge)   toBadge.textContent   = evt.to.querySelectorAll('.lead-card').length;
            },
        });
    });

    // Navigate to lead on card click (not after drag or pan)
    const board = document.getElementById('kanban-board');
    if (board) {
        let panning    = false;
        let panStartX  = 0;
        let panScrollL = 0;
        let lastPan    = 0;

        board.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            if (e.target.closest('.lead-card, a, button, input, select')) return;

            panning        = true;
            panStartX      = e.clientX;
            panScrollL     = board.scrollLeft;
            board.style.cursor      = 'grabbing';
            board.style.userSelect  = 'none';
        });

        document.addEventListener('mousemove', function (e) {
            if (!panning) return;
            board.scrollLeft = panScrollL - (e.clientX - panStartX);
        });

        document.addEventListener('mouseup', function () {
            if (!panning) return;
            panning             = false;
            lastPan             = Date.now();
            board.style.cursor     = '';
            board.style.userSelect = '';
        });

        board.addEventListener('click', function (e) {
            if (Date.now() - lastDrag < 300) return;
            if (Date.now() - lastPan  < 300) return;
            const card = e.target.closest('.lead-card');
            if (card && card.dataset.href) window.location.href = card.dataset.href;
        });
    }
});
</script>
@endpush
