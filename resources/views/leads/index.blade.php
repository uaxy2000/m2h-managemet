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
    <form method="GET" action="{{ route('leads.index') }}" id="filter-form"
          class="flex-shrink-0 flex flex-wrap items-center gap-2 px-6 py-2.5 bg-white border-b border-gray-200">
        <input type="hidden" name="pipeline" value="{{ $currentPipeline?->id }}">
        @if($filters['tag'])
        <input type="hidden" name="tag" value="{{ $filters['tag'] }}">
        @endif

        {{-- Name search --}}
        <div class="relative flex-shrink-0">
            <svg class="w-3.5 h-3.5 absolute left-2.5 top-2 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="İsim ara…"
                   class="pl-8 pr-3 py-1.5 rounded-lg border border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 w-40">
        </div>

        {{-- Assigned to (admin/super_admin only) --}}
        @if(auth()->user()->role !== 'user')
        <select name="assigned_to" onchange="document.getElementById('filter-form').submit()"
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Tüm kullanıcılar</option>
            @foreach($internalUsers as $u)
            <option value="{{ $u->id }}" @selected($filters['assigned_to'] == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @else
        <span class="text-xs text-gray-400 italic flex-shrink-0">Yalnızca sizin leadleriniz</span>
        @endif

        {{-- Source --}}
        <select name="source" onchange="document.getElementById('filter-form').submit()"
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Tüm kaynaklar</option>
            <option value="meta_ad" @selected($filters['source'] === 'meta_ad')>Meta Reklam</option>
            <option value="manual"  @selected($filters['source'] === 'manual')>Manuel</option>
            <option value="agent"   @selected($filters['source'] === 'agent')>Agent</option>
        </select>

        {{-- Program --}}
        @if($programsByCountry->isNotEmpty())
        <select name="program_id" onchange="document.getElementById('filter-form').submit()"
                class="flex-shrink-0 rounded-lg border-gray-200 text-sm py-1.5 pl-2.5 pr-7 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Tüm programlar</option>
            @foreach($programsByCountry as $country => $countryPrograms)
            <optgroup label="{{ $country }}">
                <option value="country:{{ $country }}" @selected($filters['program_id'] === 'country:'.$country)>— Tüm {{ $country }}</option>
                @foreach($countryPrograms as $prog)
                <option value="{{ $prog->id }}" @selected($filters['program_id'] === $prog->id)>{{ $prog->name }}</option>
                @endforeach
            </optgroup>
            @endforeach
        </select>
        @endif

        {{-- Duplicate toggle --}}
        <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer select-none flex-shrink-0">
            <input type="checkbox" name="duplicate" value="1" @checked($filters['duplicate'])
                   onchange="document.getElementById('filter-form').submit()"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Duplikat
        </label>

        {{-- Search submit (for text input) --}}
        <button type="submit"
                class="flex-shrink-0 px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
            Ara
        </button>

        {{-- Active filter count + clear all --}}
        @php
        $activeCount = collect([
            $filters['search'] ?: null,
            (auth()->user()->role !== 'user' && $filters['assigned_to']) ? $filters['assigned_to'] : null,
            $filters['source'] ?: null,
            $filters['program_id'] ?: null,
            $filters['duplicate'] ? 1 : null,
            $filters['tag'] ?: null,
        ])->filter()->count();
        @endphp
        @if($activeCount > 0)
        <span class="flex-shrink-0 text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">
            {{ $activeCount }} aktif
        </span>
        <a href="{{ route('leads.index', ['pipeline' => $currentPipeline?->id]) }}"
           class="flex-shrink-0 text-xs text-gray-400 hover:text-red-500 transition-colors">
            × Temizle
        </a>
        @endif
    </form>

    {{-- Tag filter grouped by group --}}
    @if($hasTags)
    @php
    $baseParams = array_filter([
        'pipeline'    => $currentPipeline?->id,
        'search'      => $filters['search'] ?: null,
        'assigned_to' => (auth()->user()->role !== 'user' && $filters['assigned_to']) ? $filters['assigned_to'] : null,
        'source'      => $filters['source'] ?: null,
        'program_id'  => $filters['program_id'] ?: null,
        'duplicate'   => $filters['duplicate'] ? 1 : null,
    ], fn ($v) => $v !== null && $v !== '');
    @endphp
    <div class="flex-shrink-0 flex flex-wrap items-center gap-x-4 gap-y-1.5 px-6 py-2 bg-gray-50 border-b border-gray-200">
        <span class="text-xs text-gray-400 font-medium flex-shrink-0">Tag:</span>

        @foreach($tagGroups as $group)
        @if($group->tags->isNotEmpty())
        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-semibold text-gray-400 flex-shrink-0">{{ $group->name }}:</span>
            @foreach($group->tags as $tag)
            @php
                $isActive = $filters['tag'] === $tag->id;
                $href = route('leads.index', array_merge($baseParams, ['tag' => $isActive ? null : $tag->id]));
            @endphp
            <a href="{{ $href }}"
               class="flex-shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors
                      {{ $isActive ? 'text-white border-transparent' : 'text-gray-500 border-gray-200 hover:border-gray-400 bg-white' }}"
               style="{{ $isActive ? 'background-color:'.$tag->color.';border-color:'.$tag->color : '' }}">
                {{ $tag->name }}
            </a>
            @endforeach
        </div>
        @endif
        @endforeach

        @if($ungroupedTags->isNotEmpty())
        <div class="flex items-center gap-1.5 flex-wrap">
            @if($tagGroups->contains(fn ($g) => $g->tags->isNotEmpty()))
            <span class="text-xs font-semibold text-gray-400 flex-shrink-0">Diğer:</span>
            @endif
            @foreach($ungroupedTags as $tag)
            @php
                $isActive = $filters['tag'] === $tag->id;
                $href = route('leads.index', array_merge($baseParams, ['tag' => $isActive ? null : $tag->id]));
            @endphp
            <a href="{{ $href }}"
               class="flex-shrink-0 px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors
                      {{ $isActive ? 'text-white border-transparent' : 'text-gray-500 border-gray-200 hover:border-gray-400 bg-white' }}"
               style="{{ $isActive ? 'background-color:'.$tag->color.';border-color:'.$tag->color : '' }}">
                {{ $tag->name }}
            </a>
            @endforeach
        </div>
        @endif

        @if($filters['tag'])
        <a href="{{ route('leads.index', $baseParams) }}"
           class="flex-shrink-0 text-xs text-gray-400 hover:text-gray-600 ml-auto">× Tag temizle</a>
        @endif
    </div>
    @endif

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

                        @if($lead->is_duplicate_flag)
                        <div class="mt-1.5 flex items-center gap-1 text-xs text-amber-600 font-medium">
                            <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                            </svg>
                            Possible duplicate
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
