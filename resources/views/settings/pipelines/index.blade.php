@extends('layouts.app')

@section('title', 'Pipelines')
@section('heading', 'Pipeline Configuration')

@section('content')
<div x-data>

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Configure your sales pipelines and their stages.</p>
        <a href="{{ route('settings.pipelines.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Pipeline
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

    @if($pipelines->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
            <p class="text-gray-400 text-sm">No pipelines yet.</p>
            <a href="{{ route('settings.pipelines.create') }}"
               class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-2 inline-block">
                Create your first pipeline →
            </a>
        </div>
    @else
        <div id="pipelines-list" class="space-y-3">
            @foreach($pipelines as $pipeline)
                <div class="pipeline-item bg-white rounded-xl border border-gray-200 flex items-center gap-4 px-5 py-4"
                     data-id="{{ $pipeline->id }}">

                    <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-400 flex-shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="7" cy="5" r="1.5"/><circle cx="13" cy="5" r="1.5"/>
                            <circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/>
                            <circle cx="7" cy="15" r="1.5"/><circle cx="13" cy="15" r="1.5"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $pipeline->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $pipeline->stages_count }} {{ $pipeline->stages_count === 1 ? 'stage' : 'stages' }}
                        </p>
                    </div>

                    <span class="text-xs font-medium px-2.5 py-1 rounded-full flex-shrink-0
                                 {{ $pipeline->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $pipeline->is_active ? 'Active' : 'Inactive' }}
                    </span>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('settings.pipelines.edit', $pipeline) }}"
                           class="text-sm text-gray-600 hover:text-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('settings.pipelines.destroy', $pipeline) }}"
                              @submit.prevent="if(confirm('Delete pipeline \'{{ addslashes($pipeline->name) }}\'? This cannot be undone.')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors"
                                    title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('pipelines-list');
    if (!list) return;

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 150,
        onEnd() {
            const ids = [...list.querySelectorAll('.pipeline-item')].map(el => el.dataset.id);
            fetch('{{ route('settings.pipelines.sort') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids }),
            });
        },
    });
});
</script>
@endpush
