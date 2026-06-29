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
            @csrf
            @method('DELETE')
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

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-2.5 text-sm mb-5">
    {{ session('success') }}
</div>
@endif
@if(session('warning'))
<div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-2.5 text-sm mb-5">
    {{ session('warning') }}
</div>
@endif

{{-- Lead name + duplicate flag --}}
<div class="flex items-center gap-3 mb-6">
    <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
        {{ $lead->initials() }}
    </div>
    <div>
        <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold text-gray-900">{{ $lead->fullName() }}</h2>
            @if($lead->is_duplicate_flag)
            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                </svg>
                Possible duplicate
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
    </div>
</div>

<div class="grid grid-cols-3 gap-5">

    {{-- Left: Contact + Deal --}}
    <div class="col-span-2 space-y-5">

        {{-- Contact info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Contact</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                @if($lead->email)
                <div>
                    <dt class="text-xs text-gray-400">Email</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">
                        <a href="mailto:{{ $lead->email }}" class="hover:text-indigo-600 transition-colors">
                            {{ $lead->email }}
                        </a>
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
            @if(!$lead->email && !$lead->phone && !$lead->whatsapp && !$lead->country_of_origin)
            <p class="text-sm text-gray-400">No contact details.</p>
            @endif
        </div>

        {{-- Deal info --}}
        @if($lead->potential_value || $lead->our_commission || $lead->expected_close_date)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Deal</h3>
            <dl class="grid grid-cols-3 gap-x-6 gap-y-3">
                @if($lead->potential_value)
                <div>
                    <dt class="text-xs text-gray-400">Potential Value</dt>
                    <dd class="text-base font-semibold text-emerald-600 mt-0.5">
                        ${{ number_format((float) $lead->potential_value) }}
                    </dd>
                </div>
                @endif
                @if($lead->our_commission)
                <div>
                    <dt class="text-xs text-gray-400">Our Commission</dt>
                    <dd class="text-base font-semibold text-indigo-600 mt-0.5">
                        ${{ number_format((float) $lead->our_commission) }}
                    </dd>
                </div>
                @endif
                @if($lead->expected_close_date)
                <div>
                    <dt class="text-xs text-gray-400">Expected Close</dt>
                    <dd class="text-sm text-gray-800 mt-0.5">
                        {{ $lead->expected_close_date->format('d M Y') }}
                    </dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

        {{-- Placeholder: Notes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Notes</h3>
            <p class="text-sm text-gray-400">Notes coming soon.</p>
        </div>

    </div>

    {{-- Right: Assignment + History --}}
    <div class="space-y-5">

        {{-- Assignment --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Assignment</h3>
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
