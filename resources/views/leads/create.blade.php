@extends('layouts.app')

@section('title', 'New Lead')
@section('heading', 'Leads')

@section('content')
<div class="mb-5">
    <a href="{{ route('leads.index', ['pipeline' => $defaultPipelineId]) }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Leads
    </a>
</div>

<div class="max-w-2xl"
     x-data="{
         pipelinesData: {{ Js::from($pipelines->map(fn ($p) => [
             'id'     => $p->id,
             'name'   => $p->name,
             'stages' => $p->stages->map(fn ($s) => [
                 'id'         => $s->id,
                 'name'       => $s->name,
                 'sub_stages' => $s->subStages->map(fn ($ss) => ['id' => $ss->id, 'name' => $ss->name])->values(),
             ])->values(),
         ])->values()) }},
         pipelineId: '{{ old('pipeline_id', $defaultPipelineId ?? '') }}',
         stageId:    '{{ old('stage_id',    $defaultStageId    ?? '') }}',
         subStageId: '{{ old('sub_stage_id', '') }}',
         get stages() {
             const p = this.pipelinesData.find(p => p.id === this.pipelineId);
             return p ? p.stages : [];
         },
         get subStages() {
             const s = this.stages.find(s => s.id === this.stageId);
             return s ? s.sub_stages : [];
         },
         onPipelineChange() { this.stageId = ''; this.subStageId = ''; },
         onStageChange()    { this.subStageId = ''; },
     }">

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-6">New Lead</h2>

        <form method="POST" action="{{ route('leads.store') }}">
            @csrf

            {{-- Name --}}
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}"
                           required autofocus
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500
                                  {{ $errors->has('first_name') ? 'border-red-400' : '' }}">
                    @error('first_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            {{-- Contact --}}
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500
                                  {{ $errors->has('email') ? 'border-red-400' : '' }}">
                    @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">WhatsApp Number</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                              focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Demographics --}}
            <div class="grid grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Country of Origin</label>
                    <input type="text" name="country_of_origin" value="{{ old('country_of_origin') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nationality</label>
                    <input type="text" name="nationality" value="{{ old('nationality') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Language</label>
                    <input type="text" name="language" value="{{ old('language') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            {{-- Pipeline / Stage / Sub-stage --}}
            <div class="grid grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Pipeline <span class="text-red-500">*</span>
                    </label>
                    <select name="pipeline_id" required x-model="pipelineId" @change="onPipelineChange()"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500
                                   {{ $errors->has('pipeline_id') ? 'border-red-400' : '' }}">
                        <option value="">Select…</option>
                        @foreach($pipelines as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('pipeline_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Stage <span class="text-red-500">*</span>
                    </label>
                    <select name="stage_id" required x-model="stageId" @change="onStageChange()"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500
                                   {{ $errors->has('stage_id') ? 'border-red-400' : '' }}">
                        <option value="">Select…</option>
                        <template x-for="s in stages" :key="s.id">
                            <option :value="s.id" x-text="s.name" :selected="s.id === stageId"></option>
                        </template>
                    </select>
                    @error('stage_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div x-show="subStages.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Sub-stage</label>
                    <select name="sub_stage_id" x-model="subStageId"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">None</option>
                        <template x-for="ss in subStages" :key="ss.id">
                            <option :value="ss.id" x-text="ss.name" :selected="ss.id === subStageId"></option>
                        </template>
                    </select>
                </div>
            </div>

            {{-- Assigned to --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Assigned To</label>
                <select name="assigned_to"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                               focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Unassigned</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('assigned_to') === $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Deal --}}
            <div class="grid grid-cols-3 gap-4 mb-7">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Potential Value ($)</label>
                    <input type="number" name="potential_value" value="{{ old('potential_value') }}"
                           min="0" step="0.01"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Our Commission ($)</label>
                    <input type="number" name="our_commission" value="{{ old('our_commission') }}"
                           min="0" step="0.01"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Expected Close</label>
                    <input type="date" name="expected_close_date" value="{{ old('expected_close_date') }}"
                           class="block w-full rounded-lg border-gray-300 text-sm shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('leads.index', ['pipeline' => $defaultPipelineId]) }}"
                   class="text-sm text-gray-600 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg
                               transition-colors font-medium">
                    Create Lead
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
