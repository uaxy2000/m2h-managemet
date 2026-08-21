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

        {{-- Assigned to filter --}}
        <select name="assigned_to"
                @disabled($ownOnly)
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed">
            @if(!$ownOnly)
            <option value="">All users</option>
            @endif
            @foreach($internalUsers as $u)
            <option value="{{ $u->id }}" @selected($ownOnly || $filters['assigned_to'] == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>

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

        {{-- All filters (custom fields) --}}
        @if($filterableFields->isNotEmpty())
        @php $activeCfCount = count($filters['cf']); @endphp
        <div x-data="{ open: false }" class="relative flex-shrink-0" @click.outside="open = false">

            <button type="button" @click="open = !open"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border transition-colors
                           {{ $activeCfCount ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                </svg>
                All filters
                @if($activeCfCount)
                <span class="text-xs font-semibold bg-indigo-200 text-indigo-800 rounded-full px-1.5 py-0.5 leading-none">{{ $activeCfCount }}</span>
                @endif
                <svg class="w-3 h-3 ml-0.5 flex-shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"
                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>

            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute left-0 top-full mt-1 bg-white rounded-xl border border-gray-200 shadow-lg z-50 p-4 min-w-56"
                 style="width: max-content; max-width: 22rem;">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Filter by field</p>
                <div class="grid gap-3 {{ $filterableFields->count() > 2 ? 'grid-cols-2' : 'grid-cols-1' }}">
                    @foreach($filterableFields as $cf)
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $cf->label }}</label>
                        <select name="cf[{{ $cf->key }}]"
                                class="w-full rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All</option>
                            @foreach($cf->options as $opt)
                            <option value="{{ $opt->value }}" @selected(($filters['cf'][$cf->key] ?? '') === $opt->value)>{{ $opt->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Duplicate toggle --}}
        <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer select-none flex-shrink-0">
            <input type="checkbox" name="duplicate" value="1" @checked($filters['duplicate'])
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Duplicate
        </label>

        {{-- Sort --}}
        <div class="flex items-center gap-1 flex-shrink-0 ml-auto">
            <span class="text-xs text-gray-400">Sort:</span>
            <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                <button type="submit" name="sort" value="application_date"
                        class="px-2.5 py-1.5 transition-colors {{ $filters['sort'] === 'application_date' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">
                    Application Date
                </button>
                <button type="submit" name="sort" value="stage_entered_at"
                        class="px-2.5 py-1.5 border-l border-gray-200 transition-colors {{ $filters['sort'] === 'stage_entered_at' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">
                    Stage Entry Date
                </button>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="flex-shrink-0 px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
            Search
        </button>

        {{-- Active filter count + clear all --}}
        @php
        $activeCount = collect([
            $filters['search'] ?: null,
            (!$ownOnly && $filters['assigned_to']) ? $filters['assigned_to'] : null,
            $filters['source'] ?: null,
            $filters['program_id'] ?: null,
            $filters['duplicate'] ? 1 : null,
            count($filters['tags']) > 0 ? 1 : null,
            count($filters['cf']) > 0 ? 1 : null,
        ])->filter()->count();
        @endphp
        @if($activeCount > 0)
        <span class="flex-shrink-0 text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">
            {{ $activeCount }} active
        </span>
        <a href="{{ route('leads.index', array_filter(['pipeline' => $currentPipeline?->id, 'sort' => $filters['sort'] !== 'application_date' ? $filters['sort'] : null])) }}"
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

    <style>
    @keyframes wa-ping {
        75%, 100% { transform: scale(2); opacity: 0; }
    }
    </style>

    {{-- Kanban board --}}
    <div class="flex-1 overflow-x-auto overflow-y-hidden cursor-grab" id="kanban-board">
        <div class="flex gap-4 h-full px-6 py-5">

            @forelse($currentPipeline->stages as $stage)
            @if(strtolower($stage->name) === 'lead received' && !auth()->user()->isAdmin())
                @continue
            @endif
            <div class="flex flex-col w-72 flex-shrink-0 h-full">

                {{-- Column header --}}
                <div class="flex items-center gap-2 mb-3 px-1">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                         style="background-color: {{ $stage->color }}"></div>
                    <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider truncate">
                        {{ $stage->name }}
                    </span>
                    @php $stageShown = $stage->leads->count(); $stageTotal = $stageTotals[$stage->id] ?? $stageShown; @endphp
                    <span class="ml-auto text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full stage-count flex-shrink-0"
                          data-total="{{ $stageTotal }}">
                        {{ $stageShown < $stageTotal ? $stageShown . ' / ' . $stageTotal : $stageTotal }}
                    </span>
                </div>

                {{-- Cards --}}
                <div class="stage-column flex-1 overflow-y-auto space-y-2.5 pb-2 min-h-16 rounded-lg transition-colors"
                     data-stage="{{ $stage->id }}">
                    @foreach($stage->leads as $lead)
                        @include('leads._kanban_card')
                    @endforeach
                    @if($stage->leads->count() < ($stageTotals[$stage->id] ?? 0))
                    <div class="kanban-sentinel h-4 flex-shrink-0"
                         data-stage-id="{{ $stage->id }}"
                         data-page="2"
                         data-loading="0"></div>
                    @endif
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
    const csrf        = document.querySelector('meta[name="csrf-token"]').content;
    const filterQuery = window.location.search.slice(1); // pass current filters to AJAX
    let lastDrag = 0;

    function refreshBadge(col, totalDelta) {
        const badge = col.closest('.flex.flex-col')?.querySelector('.stage-count');
        if (!badge) return;
        const total = (parseInt(badge.dataset.total) || 0) + totalDelta;
        badge.dataset.total = total;
        const shown = col.querySelectorAll('.lead-card').length;
        badge.textContent = shown < total ? `${shown} / ${total}` : String(total);
    }

    function setupSentinel(sentinel) {
        const col = sentinel.closest('.stage-column');
        if (!col) return;
        const obs = new IntersectionObserver(([entry]) => {
            if (!entry.isIntersecting || sentinel.dataset.loading === '1') return;
            sentinel.dataset.loading = '1';
            const stageId = sentinel.dataset.stageId;
            const page    = sentinel.dataset.page;
            const url     = `/kanban-cards/${stageId}?page=${page}${filterQuery ? '&' + filterQuery : ''}`;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    sentinel.insertAdjacentHTML('beforebegin', data.html);
                    // Update badge
                    const badge = col.closest('.flex.flex-col')?.querySelector('.stage-count');
                    if (badge) {
                        badge.dataset.total = data.total;
                        badge.textContent = data.shown < data.total
                            ? `${data.shown} / ${data.total}`
                            : String(data.total);
                    }
                    if (data.next_page) {
                        sentinel.dataset.page    = data.next_page;
                        sentinel.dataset.loading = '0';
                    } else {
                        obs.unobserve(sentinel);
                        sentinel.remove();
                    }
                })
                .catch(() => { sentinel.dataset.loading = '0'; });
        }, { root: col, threshold: 0.1 });
        obs.observe(sentinel);
    }

    document.querySelectorAll('.kanban-sentinel').forEach(setupSentinel);

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

                // Refresh count badges (maintain shown/total format)
                refreshBadge(evt.from, -1);
                refreshBadge(evt.to, +1);
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
            if (!card || !card.dataset.href) return;

            // Save full URL + scroll state before navigating
            const colScrolls = {};
            document.querySelectorAll('.stage-column').forEach(col => {
                if (col.scrollTop > 0) colScrolls[col.dataset.stage] = col.scrollTop;
            });
            sessionStorage.setItem('kanban_scroll', JSON.stringify({
                returnUrl: window.location.href,
                boardLeft: board.scrollLeft,
                columns:   colScrolls,
            }));

            window.location.href = card.dataset.href;
        });
    }

    // Restore scroll state when returning from lead detail
    const saved = sessionStorage.getItem('kanban_scroll');
    if (saved && board) {
        try {
            const state = JSON.parse(saved);
            board.scrollLeft = state.boardLeft || 0;
            document.querySelectorAll('.stage-column').forEach(col => {
                const top = state.columns?.[col.dataset.stage];
                if (top) col.scrollTop = top;
            });
        } catch (_) {}
        sessionStorage.removeItem('kanban_scroll');
    }
});

</script>
@endpush

@push('styles')
<style>
.stage-column::-webkit-scrollbar        { width: 3px; }
.stage-column::-webkit-scrollbar-track  { background: transparent; }
.stage-column::-webkit-scrollbar-thumb  { background: #d1d5db; border-radius: 9999px; }
.stage-column::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
.stage-column { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
</style>
@endpush
