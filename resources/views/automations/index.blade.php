@extends('layouts.app')

@section('heading', 'Automations')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Automation Rules</h2>
            <p class="text-sm text-gray-500 mt-0.5">Rules run automatically on leads. First matching rule wins.</p>
        </div>
        <a href="{{ route('automations.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Rule
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Rules list --}}
    <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        @forelse($rules as $rule)
            <div class="flex items-center gap-4 px-5 py-4" x-data="{ active: {{ $rule->is_active ? 'true' : 'false' }} }">
                {{-- Priority badge --}}
                <span class="w-8 h-8 flex items-center justify-center text-xs font-bold rounded-full bg-gray-100 text-gray-500 flex-shrink-0"
                      title="Priority">{{ $rule->priority }}</span>

                {{-- Name & details --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-gray-800 text-sm">{{ $rule->name }}</span>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $rule->re_run_mode === 'once' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600' }}">
                            {{ $rule->re_run_mode === 'once' ? 'Run once' : 'Run always' }}
                        </span>
                        @foreach($rule->trigger_events as $ev)
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">{{ str_replace('_', ' ', $ev) }}</span>
                        @endforeach
                    </div>
                    @if($rule->description)
                        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $rule->description }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $rule->conditions->count() }} condition{{ $rule->conditions->count() !== 1 ? 's' : '' }}
                        · {{ $rule->actions->count() }} action{{ $rule->actions->count() !== 1 ? 's' : '' }}
                        · {{ $rule->runs_count }} run{{ $rule->runs_count !== 1 ? 's' : '' }}
                    </p>
                </div>

                {{-- Active toggle --}}
                <button @click="
                    fetch('{{ route('automations.toggle', $rule) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})
                    .then(r => r.json()).then(d => active = d.is_active)"
                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                    :class="active ? 'bg-indigo-600' : 'bg-gray-200'"
                    title="Toggle active">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                          :class="active ? 'translate-x-4' : 'translate-x-0'"></span>
                </button>

                {{-- Preview count --}}
                <div x-data="{ count: null, alreadyRan: 0, reRunMode: '', loading: false }">
                    <button @click="loading=true; fetch('{{ route('automations.preview', $rule) }}',{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{count=d.count;alreadyRan=d.already_ran;reRunMode=d.re_run_mode;loading=false;})"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap"
                            title="Preview: how many leads match now">
                        <span x-show="!loading && count === null">Preview</span>
                        <span x-show="loading">…</span>
                        <span x-show="count !== null && !loading"
                              x-text="reRunMode === 'once' && alreadyRan > 0
                                  ? count + ' matching (' + alreadyRan + ' run before)'
                                  : count + ' leads'"></span>
                    </button>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 flex-shrink-0">
                    @if(in_array('manual', $rule->trigger_events ?? []))
                        <form method="POST" action="{{ route('automations.run', $rule) }}"
                              onsubmit="return confirm('Run \'{{ addslashes($rule->name) }}\' against all leads now?')">
                            @csrf
                            <button type="submit" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Run now">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/>
                                </svg>
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('automations.edit', $rule) }}"
                       class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('automations.destroy', $rule) }}"
                          onsubmit="return confirm('Delete rule \'{{ addslashes($rule->name) }}\'?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-sm text-gray-400">
                No automation rules yet.
                <a href="{{ route('automations.create') }}" class="text-indigo-600 hover:underline ml-1">Create one →</a>
            </div>
        @endforelse
    </div>

    {{-- Conflicts panel --}}
    @if($conflicts->isNotEmpty())
        <div class="bg-white rounded-xl border border-amber-200">
            <div class="px-5 py-3 border-b border-amber-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <span class="text-sm font-semibold text-amber-700">Rule Conflicts (last 20)</span>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($conflicts as $conflict)
                    <div class="px-5 py-3 flex items-center gap-3 text-xs">
                        <div class="flex-1 min-w-0">
                            <span class="font-medium text-gray-700">{{ $conflict->rule?->name ?? 'Deleted rule' }}</span>
                            <span class="text-gray-400 mx-1">on</span>
                            @if($conflict->lead)
                                <a href="{{ route('leads.show', $conflict->lead) }}" class="text-indigo-600 hover:underline">
                                    {{ $conflict->lead->first_name }} {{ $conflict->lead->last_name }}
                                </a>
                            @else
                                <span class="text-gray-400">Deleted lead</span>
                            @endif
                            <span class="text-gray-400 ml-1">— also matched but was blocked by first-match rule</span>
                        </div>
                        <span class="text-gray-400 flex-shrink-0">{{ $conflict->created_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
