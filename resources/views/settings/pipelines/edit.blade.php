@extends('layouts.app')

@section('title', $pipeline->name)
@section('heading', 'Pipeline Configuration')

@section('content')
<div
    x-data="{
        showStageModal: false,
        stageMode: 'add',
        stage: { id: '', name: '', color: '#6366f1' },
        colors: ['#6366f1','#8b5cf6','#ec4899','#ef4444','#f97316','#eab308','#22c55e','#14b8a6','#3b82f6','#64748b'],

        showSubModal: false,
        subMode: 'add',
        sub: { id: '', name: '', stageId: '' },

        addStage() {
            this.stageMode = 'add';
            this.stage = { id: '', name: '', color: '#6366f1' };
            this.showStageModal = true;
        },
        editStage(id, name, color) {
            this.stageMode = 'edit';
            this.stage = { id, name, color };
            this.showStageModal = true;
        },
        addSub(stageId) {
            this.subMode = 'add';
            this.sub = { id: '', name: '', stageId };
            this.showSubModal = true;
        },
        editSub(stageId, id, name) {
            this.subMode = 'edit';
            this.sub = { id, name, stageId };
            this.showSubModal = true;
        },
    }"
>

{{-- Back link --}}
<div class="mb-6">
    <a href="{{ route('settings.pipelines.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Pipelines
    </a>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-5 text-sm">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
        {{ session('error') }}
    </div>
@endif

{{-- Pipeline settings --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-5">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Pipeline Settings</h2>
    <form method="POST" action="{{ route('settings.pipelines.update', $pipeline) }}">
        @csrf
        @method('PUT')
        <div class="flex items-end gap-4">
            <div class="flex-1">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $pipeline->name) }}"
                    required
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                >
                @error('name')
                    <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>
            <label class="flex items-center gap-2 mb-0.5 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    {{ $pipeline->is_active ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                >
                <span class="text-sm text-gray-700">Active</span>
            </label>
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex-shrink-0">
                Save
            </button>
        </div>
    </form>
</div>

{{-- Stages --}}
<div class="bg-white rounded-xl border border-gray-200">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="text-sm font-semibold text-gray-700">
            Stages
            <span class="ml-1.5 text-xs font-normal text-gray-400">drag to reorder</span>
        </h2>
        <button @click="addStage()"
                class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Stage
        </button>
    </div>

    @if($pipeline->stages->isEmpty())
        <div class="px-6 py-12 text-center text-sm text-gray-400">
            No stages yet. Click "Add Stage" to get started.
        </div>
    @else
        <ul id="stages-list" class="divide-y divide-gray-100">
            @foreach($pipeline->stages as $stage)
                <li class="stage-item" data-id="{{ $stage->id }}">

                    {{-- Stage row --}}
                    <div class="flex items-center gap-3 px-6 py-3">
                        <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/>
                                <circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/>
                                <circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/>
                            </svg>
                        </div>
                        <div class="w-3 h-3 rounded-full flex-shrink-0"
                             style="background-color: {{ $stage->color }}"></div>
                        <span class="flex-1 text-sm font-medium text-gray-700">{{ $stage->name }}</span>
                        <button
                            @click="editStage('{{ $stage->id }}', '{{ addslashes($stage->name) }}', '{{ $stage->color }}')"
                            class="text-xs text-gray-500 hover:text-gray-700 px-2.5 py-1 rounded-lg hover:bg-gray-100 transition-colors">
                            Edit
                        </button>
                        <form method="POST" action="{{ route('settings.stages.destroy', [$pipeline, $stage]) }}"
                              @submit.prevent="if(confirm('Delete stage \'{{ addslashes($stage->name) }}\'?')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-1 text-gray-400 hover:text-red-500 rounded hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    {{-- Sub-stages --}}
                    <div class="pl-14 pr-6 pb-3">
                        <ul id="sub-stages-{{ $stage->id }}"
                            class="sub-stage-list space-y-1 mb-2"
                            data-stage="{{ $stage->id }}">
                            @foreach($stage->subStages as $sub)
                                <li class="sub-stage-item flex items-center gap-2 group py-0.5"
                                    data-id="{{ $sub->id }}">
                                    <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/>
                                            <circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/>
                                            <circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/>
                                        </svg>
                                    </div>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                                    <span class="flex-1 text-xs text-gray-600">{{ $sub->name }}</span>
                                    <div class="opacity-0 group-hover:opacity-100 flex items-center gap-1 transition-opacity">
                                        <button
                                            @click="editSub('{{ $stage->id }}', '{{ $sub->id }}', '{{ addslashes($sub->name) }}')"
                                            class="text-xs text-gray-400 hover:text-gray-600 px-1.5 py-0.5 rounded hover:bg-gray-100">
                                            Edit
                                        </button>
                                        <form method="POST"
                                              action="{{ route('settings.sub-stages.destroy', [$stage, $sub]) }}"
                                              @submit.prevent="if(confirm('Delete sub-stage \'{{ addslashes($sub->name) }}\'?')) $el.submit()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-0.5 text-gray-400 hover:text-red-500 rounded hover:bg-red-50 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <button @click="addSub('{{ $stage->id }}')"
                                class="inline-flex items-center gap-1 text-xs text-indigo-500 hover:text-indigo-700 transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add sub-stage
                        </button>
                    </div>

                </li>
            @endforeach
        </ul>
    @endif
</div>

{{-- ── Stage Modal ─────────────────────────────────────────────── --}}
<div x-show="showStageModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showStageModal = false"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">

        <h3 class="text-base font-semibold text-gray-800 mb-5"
            x-text="stageMode === 'add' ? 'Add Stage' : 'Edit Stage'"></h3>

        <form
            :action="stageMode === 'add'
                ? '{{ route('settings.stages.store', $pipeline) }}'
                : '{{ route('settings.stages.store', $pipeline) }}/' + stage.id"
            method="POST"
        >
            @csrf
            <template x-if="stageMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                <input type="text" name="name" :value="stage.name" required autofocus
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2.5">Color</label>
                <div class="flex flex-wrap gap-2.5">
                    <template x-for="c in colors" :key="c">
                        <button
                            type="button"
                            class="w-7 h-7 rounded-full transition-all duration-100"
                            :style="`background-color: ${c}`"
                            :class="stage.color === c
                                ? 'ring-2 ring-offset-2 ring-gray-500 scale-110'
                                : 'hover:scale-110 hover:ring-2 hover:ring-offset-1 hover:ring-gray-300'"
                            @click="stage.color = c"
                        ></button>
                    </template>
                </div>
                <input type="hidden" name="color" :value="stage.color">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" @click="showStageModal = false"
                        class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Save Stage
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Sub-stage Modal ─────────────────────────────────────────── --}}
<div x-show="showSubModal" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showSubModal = false"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6">

        <h3 class="text-base font-semibold text-gray-800 mb-5"
            x-text="subMode === 'add' ? 'Add Sub-stage' : 'Edit Sub-stage'"></h3>

        <form
            :action="subMode === 'add'
                ? '{{ url('settings/stages') }}/' + sub.stageId + '/sub-stages'
                : '{{ url('settings/stages') }}/' + sub.stageId + '/sub-stages/' + sub.id"
            method="POST"
        >
            @csrf
            <template x-if="subMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Name</label>
                <input type="text" name="name" :value="sub.name" required autofocus
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" @click="showSubModal = false"
                        class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function postSort(url, ids) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ ids }),
        });
    }

    // Stages sort
    const stagesList = document.getElementById('stages-list');
    if (stagesList) {
        Sortable.create(stagesList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd() {
                const ids = [...stagesList.querySelectorAll('.stage-item')].map(el => el.dataset.id);
                postSort('{{ route('settings.stages.sort', $pipeline) }}', ids);
            },
        });
    }

    // Sub-stages sort (one Sortable per stage)
    document.querySelectorAll('.sub-stage-list').forEach(list => {
        Sortable.create(list, {
            handle: '.drag-handle',
            animation: 150,
            onEnd() {
                const stageId = list.dataset.stage;
                const ids = [...list.querySelectorAll('.sub-stage-item')].map(el => el.dataset.id);
                postSort(`{{ url('settings/stages') }}/${stageId}/sub-stages/sort`, ids);
            },
        });
    });
});
</script>
@endpush
