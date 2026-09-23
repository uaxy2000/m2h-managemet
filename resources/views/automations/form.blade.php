@extends('layouts.app')

@section('heading', isset($rule) ? 'Edit Automation Rule' : 'New Automation Rule')

@section('content')
@php
    $isEdit      = isset($rule);
    $stagesJson  = $stages->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'pipeline' => $s->pipeline?->name])->toJson();
    $tagsJson    = $tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])->toJson();

    // Grouped tags for optgroup select
    $tagsGroupedArr = [];
    foreach ($tagGroups as $tg) {
        if ($tg->tags->isNotEmpty()) {
            $tagsGroupedArr[] = ['name' => $tg->name, 'tags' => $tg->tags->sortBy('name')->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->values()];
        }
    }
    $ungroupedTagsList = $tags->filter(fn($t) => is_null($t->tag_group_id))->sortBy('name');
    if ($ungroupedTagsList->isNotEmpty()) {
        $tagsGroupedArr[] = ['name' => 'Other', 'tags' => $ungroupedTagsList->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->values()];
    }
    $tagsGroupedJson = json_encode($tagsGroupedArr);
    $usersJson   = $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->toJson();
    $waJson      = $waTemplates->map(fn($t) => ['id' => $t->id, 'name' => $t->display_name ?: $t->name])->toJson();
    $cfJson      = $customFields->map(fn($f) => [
        'id'      => $f->id,
        'key'     => $f->key,
        'label'   => $f->label,
        'type'    => $f->type,
        'options' => $f->options->map(fn($o) => ['value' => $o->value, 'label' => $o->label]),
    ])->toJson();

    // Existing data for edit mode
    $initConditions = '[]';
    $initActions    = '[]';
    if ($isEdit) {
        $condData = [];
        foreach ($rule->conditions as $c) {
            $condData[] = [
                'group_index' => $c->group_index,
                'field'       => $c->field,
                'field_key'   => $c->field_key,
                'operator'    => $c->operator,
                'value'       => $c->value ?? [],
            ];
        }
        $actData = [];
        foreach ($rule->actions as $a) {
            $actData[] = [
                'action_type' => $a->action_type,
                'parameters'  => $a->parameters ?? (object)[],
            ];
        }
        $initConditions = json_encode($condData);
        $initActions    = json_encode($actData);
    }
@endphp

<div class="max-w-3xl mx-auto"
     x-data="ruleBuilder({{ $stagesJson }}, {{ $tagsJson }}, {{ $usersJson }}, {{ $waJson }}, {{ $cfJson }}, {{ $initConditions }}, {{ $initActions }}, {{ $tagsGroupedJson }})">

    <form method="POST"
          action="{{ $isEdit ? route('automations.update', $rule) : route('automations.store') }}"
          @submit="serializeHidden()">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="space-y-6">

            {{-- Basic info --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700">Rule Details</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required maxlength="191"
                               value="{{ old('name', $rule->name ?? '') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <input type="text" name="description" maxlength="500"
                               value="{{ old('description', $rule->description ?? '') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Priority <span class="text-gray-400">(lower = runs first)</span></label>
                        <input type="number" name="priority" min="1" max="999" required
                               value="{{ old('priority', $rule->priority ?? 10) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end pb-0.5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', ($rule->is_active ?? true) ? '1' : '') ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 font-medium">Active</span>
                        </label>
                    </div>
                </div>

                {{-- Triggers --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Trigger Events <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-3">
                        @foreach(['lead_created' => 'Lead Created', 'lead_updated' => 'Lead Updated', 'manual' => 'Manual'] as $val => $label)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="trigger_events[]" value="{{ $val }}"
                                       {{ in_array($val, old('trigger_events', $rule->trigger_events ?? [])) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Re-run mode --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Re-run Mode</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="re_run_mode" value="once"
                                   {{ old('re_run_mode', $rule->re_run_mode ?? 'once') === 'once' ? 'checked' : '' }}
                                   class="w-4 h-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Once per lead</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="re_run_mode" value="always"
                                   {{ old('re_run_mode', $rule->re_run_mode ?? 'once') === 'always' ? 'checked' : '' }}
                                   class="w-4 h-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Every time conditions are met</span>
                        </label>
                    </div>
                </div>

                {{-- Skip duplicates --}}
                <div class="border-t border-gray-100 pt-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="skip_duplicates" value="1"
                               {{ old('skip_duplicates', $rule->skip_duplicates ?? false) ? 'checked' : '' }}
                               class="mt-0.5 w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="text-sm text-gray-700 font-medium">Skip leads flagged as possible duplicate</span>
                            <p class="text-xs text-gray-400 mt-0.5">Leads with the "Possible duplicate" flag will be excluded from this rule — useful when duplicates require manual review.</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Condition builder --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700">Conditions</h3>
                    <span class="text-xs text-gray-400">Groups are OR'd · Conditions within a group are AND'd</span>
                </div>

                <div class="space-y-3">
                    <template x-for="(group, gi) in conditionGroups" :key="gi">
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            {{-- Group header --}}
                            <div class="bg-gray-50 px-3 py-2 flex items-center justify-between border-b border-gray-200">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                                      x-text="gi === 0 ? 'IF' : 'OR IF'"></span>
                                <button type="button" @click="removeGroup(gi)"
                                        x-show="conditionGroups.length > 1"
                                        class="text-xs text-red-400 hover:text-red-600">Remove group</button>
                            </div>
                            {{-- Conditions in group --}}
                            <div class="divide-y divide-gray-100">
                                <template x-for="(cond, ci) in group" :key="ci">
                                    <div class="px-3 py-2.5 flex items-start gap-2 flex-wrap">
                                        <span class="text-xs text-gray-400 pt-2 w-6 flex-shrink-0"
                                              x-text="ci === 0 ? '' : 'AND'"></span>

                                        {{-- Field select --}}
                                        <select x-model="cond.field" @change="onFieldChange(cond)"
                                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            <option value="">Select field…</option>
                                            <option value="stage">Stage</option>
                                            <option value="tag">Tag</option>
                                            <option value="source">Source</option>
                                            <option value="assigned_user">Assigned User</option>
                                            <option value="country">Country</option>
                                            <option value="custom_field">Custom Field</option>
                                        </select>

                                        {{-- Custom field key select --}}
                                        <template x-if="cond.field === 'custom_field'">
                                            <select x-model="cond.field_key" @change="onFieldKeyChange(cond)"
                                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Select field…</option>
                                                <template x-for="cf in customFields" :key="cf.id">
                                                    <option :value="cf.id" x-text="cf.label" :selected="cf.id == cond.field_key"></option>
                                                </template>
                                            </select>
                                        </template>

                                        {{-- Operator --}}
                                        <template x-if="cond.field">
                                            <select x-model="cond.operator"
                                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <template x-for="op in getOperators(cond)" :key="op.value">
                                                    <option :value="op.value" x-text="op.label"></option>
                                                </template>
                                            </select>
                                        </template>

                                        {{-- Value — stage (grouped by pipeline) --}}
                                        <template x-if="cond.field === 'stage' && !['is_empty','is_not_empty'].includes(cond.operator)">
                                            <select x-model="cond.value[0]"
                                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Select stage…</option>
                                                <template x-for="group in stagesGrouped" :key="group.pipeline">
                                                    <optgroup :label="group.pipeline">
                                                        <template x-for="s in group.stages" :key="s.id">
                                                            <option :value="s.id" x-text="s.name" :selected="s.id == cond.value[0]"></option>
                                                        </template>
                                                    </optgroup>
                                                </template>
                                            </select>
                                        </template>

                                        {{-- Value — tag (grouped by tag group) --}}
                                        <template x-if="cond.field === 'tag' && !['is_empty','is_not_empty'].includes(cond.operator)">
                                            <select x-model="cond.value[0]"
                                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Select tag…</option>
                                                <template x-for="group in tagsGrouped" :key="group.name">
                                                    <optgroup :label="group.name">
                                                        <template x-for="t in group.tags" :key="t.id">
                                                            <option :value="t.id" x-text="t.name" :selected="t.id == cond.value[0]"></option>
                                                        </template>
                                                    </optgroup>
                                                </template>
                                            </select>
                                        </template>

                                        {{-- Value — assigned_user --}}
                                        <template x-if="cond.field === 'assigned_user' && !['is_empty','is_not_empty'].includes(cond.operator)">
                                            <select x-model="cond.value[0]"
                                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Select user…</option>
                                                <template x-for="u in users" :key="u.id">
                                                    <option :value="u.id" x-text="u.name" :selected="u.id == cond.value[0]"></option>
                                                </template>
                                            </select>
                                        </template>

                                        {{-- Value — source / country (text input) --}}
                                        <template x-if="['source','country'].includes(cond.field) && !['is_empty','is_not_empty'].includes(cond.operator)">
                                            <input type="text" x-model="cond.value[0]" placeholder="Enter value…"
                                                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 w-40">
                                        </template>

                                        {{-- Value — custom_field (select or text) --}}
                                        <template x-if="cond.field === 'custom_field' && cond.field_key && !['is_empty','is_not_empty'].includes(cond.operator)">
                                            <template x-if="getCustomField(cond.field_key)?.type === 'select' || getCustomField(cond.field_key)?.type === 'multi_select'">
                                                <select x-model="cond.value[0]"
                                                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                    <option value="">Select option…</option>
                                                    <template x-for="opt in getCustomField(cond.field_key)?.options ?? []" :key="opt.value">
                                                        <option :value="opt.value" x-text="opt.label" :selected="opt.value == cond.value[0]"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="getCustomField(cond.field_key)?.type === 'date'">
                                                <input type="date" x-model="cond.value[0]"
                                                       class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            </template>
                                            <template x-if="getCustomField(cond.field_key)?.type === 'text'">
                                                <input type="text" x-model="cond.value[0]" placeholder="Enter value…"
                                                       class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 w-40">
                                            </template>
                                        </template>

                                        {{-- Remove condition --}}
                                        <button type="button" @click="removeCondition(gi, ci)"
                                                class="ml-auto p-1 text-gray-300 hover:text-red-500 flex-shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            {{-- Add condition button --}}
                            <div class="px-3 py-2 bg-gray-50 border-t border-gray-100">
                                <button type="button" @click="addCondition(gi)"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                    + Add condition (AND)
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addGroup()"
                        class="mt-3 text-xs text-gray-500 hover:text-indigo-600 font-medium border border-dashed border-gray-300 hover:border-indigo-400 rounded-lg px-3 py-1.5 w-full transition-colors">
                    + Add condition group (OR)
                </button>
            </div>

            {{-- Action builder --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Actions <span class="text-gray-400 font-normal text-xs">(run in order)</span></h3>

                <div class="space-y-2">
                    <template x-for="(action, ai) in actions" :key="ai">
                        <div class="border border-gray-200 rounded-lg p-3 flex items-start gap-3 flex-wrap">
                            <span class="w-5 h-5 flex items-center justify-center text-[10px] font-bold text-gray-400 bg-gray-100 rounded-full flex-shrink-0 mt-0.5"
                                  x-text="ai + 1"></span>

                            {{-- Action type --}}
                            <select x-model="action.action_type" @change="onActionTypeChange(action)"
                                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">Select action…</option>
                                <option value="assign_to">Assign to user</option>
                                <option value="change_stage">Change stage</option>
                                <option value="send_wa">Send WhatsApp message</option>
                                <option value="add_tag">Add tag</option>
                                <option value="send_notification">Send in-app notification</option>
                            </select>

                            {{-- assign_to --}}
                            <template x-if="action.action_type === 'assign_to'">
                                <select x-model="action.parameters.user_id"
                                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option value="">Select user…</option>
                                    <template x-for="u in users" :key="u.id">
                                        <option :value="u.id" x-text="u.name" :selected="u.id == action.parameters.user_id"></option>
                                    </template>
                                </select>
                            </template>

                            {{-- change_stage --}}
                            <template x-if="action.action_type === 'change_stage'">
                                <select x-model="action.parameters.stage_id"
                                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option value="">Select stage…</option>
                                    <template x-for="group in stagesGrouped" :key="group.pipeline">
                                        <optgroup :label="group.pipeline">
                                            <template x-for="s in group.stages" :key="s.id">
                                                <option :value="s.id" x-text="s.name" :selected="s.id == action.parameters.stage_id"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                </select>
                            </template>

                            {{-- send_wa --}}
                            <template x-if="action.action_type === 'send_wa'">
                                <select x-model="action.parameters.template_id"
                                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option value="">Select template…</option>
                                    <template x-for="t in waTemplates" :key="t.id">
                                        <option :value="t.id" x-text="t.name" :selected="t.id == action.parameters.template_id"></option>
                                    </template>
                                </select>
                            </template>

                            {{-- add_tag --}}
                            <template x-if="action.action_type === 'add_tag'">
                                <select x-model="action.parameters.tag_id"
                                        class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option value="">Select tag…</option>
                                    <template x-for="group in tagsGrouped" :key="group.name">
                                        <optgroup :label="group.name">
                                            <template x-for="t in group.tags" :key="t.id">
                                                <option :value="t.id" x-text="t.name" :selected="t.id == action.parameters.tag_id"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                </select>
                            </template>

                            {{-- send_notification --}}
                            <template x-if="action.action_type === 'send_notification'">
                                <div class="flex gap-2 flex-1">
                                    <input type="text" x-model="action.parameters.title" placeholder="Title…"
                                           class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 flex-1">
                                    <input type="text" x-model="action.parameters.body" placeholder="Body (optional)…"
                                           class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 flex-1">
                                </div>
                            </template>

                            <button type="button" @click="removeAction(ai)"
                                    class="ml-auto p-1 text-gray-300 hover:text-red-500 flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addAction()"
                        class="mt-3 text-xs text-gray-500 hover:text-indigo-600 font-medium border border-dashed border-gray-300 hover:border-indigo-400 rounded-lg px-3 py-1.5 w-full transition-colors">
                    + Add action
                </button>
            </div>

            {{-- Preview + Submit --}}
            <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-5 py-4">
                <div x-data="{ count: null, loading: false }" class="flex items-center gap-3">
                    @if($isEdit)
                    <button type="button"
                            @click="loading=true; fetch('{{ route('automations.preview', $rule) }}',{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{count=d.count;loading=false;})"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <span x-show="!loading && count === null">Preview matching leads</span>
                        <span x-show="loading">Counting…</span>
                        <span x-show="count !== null && !loading" x-text="count + ' leads currently match'"></span>
                    </button>
                    @else
                        <span class="text-xs text-gray-400">Save first to preview matching leads.</span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('automations.index') }}"
                       class="text-sm text-gray-500 hover:text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        {{ $isEdit ? 'Update Rule' : 'Create Rule' }}
                    </button>
                </div>
            </div>

        </div>

        {{-- Hidden serialized fields --}}
        <input type="hidden" name="conditions_json" x-ref="condJson">
        <input type="hidden" name="actions_json" x-ref="actJson">
    </form>
</div>

@push('scripts')
<script>
function ruleBuilder(stages, tags, users, waTemplates, customFields, initConditions, initActions, tagsGrouped) {
    return {
        stages,
        tags,
        tagsGrouped,
        stagesGrouped: [],
        users,
        waTemplates,
        customFields,

        // conditionGroups: array of groups; each group is array of condition objects
        conditionGroups: [],
        actions: [],

        init() {
            // Group stages by pipeline
            const stageMap = {};
            this.stages.forEach(s => {
                const p = s.pipeline || 'No Pipeline';
                if (!stageMap[p]) stageMap[p] = { pipeline: p, stages: [] };
                stageMap[p].stages.push(s);
            });
            this.stagesGrouped = Object.values(stageMap);

            if (initConditions && initConditions.length > 0) {
                // Rebuild groups from flat list
                const grouped = {};
                initConditions.forEach(c => {
                    const g = c.group_index ?? 0;
                    if (!grouped[g]) grouped[g] = [];
                    grouped[g].push({ ...c, value: c.value ?? [''] });
                });
                const keys = Object.keys(grouped).map(Number).sort((a,b) => a-b);
                this.conditionGroups = keys.map(k => grouped[k]);
            } else {
                this.conditionGroups = [[this.newCondition()]];
            }

            if (initActions && initActions.length > 0) {
                this.actions = initActions.map(a => ({
                    action_type: a.action_type,
                    parameters: a.parameters ?? {},
                }));
            } else {
                this.actions = [];
            }
        },

        newCondition() {
            return { field: '', field_key: null, operator: 'is', value: [''] };
        },

        addGroup() {
            this.conditionGroups.push([this.newCondition()]);
        },

        removeGroup(gi) {
            if (this.conditionGroups.length > 1) {
                this.conditionGroups.splice(gi, 1);
            }
        },

        addCondition(gi) {
            this.conditionGroups[gi].push(this.newCondition());
        },

        removeCondition(gi, ci) {
            const group = this.conditionGroups[gi];
            group.splice(ci, 1);
            if (group.length === 0) {
                if (this.conditionGroups.length > 1) {
                    this.conditionGroups.splice(gi, 1);
                } else {
                    this.conditionGroups[gi] = [this.newCondition()];
                }
            }
        },

        onFieldChange(cond) {
            cond.field_key = null;
            cond.value = [''];
            const ops = this.getOperators(cond);
            cond.operator = ops.length ? ops[0].value : 'is';
        },

        onFieldKeyChange(cond) {
            cond.value = [''];
        },

        getOperators(cond) {
            const field = cond.field;
            if (field === 'tag') {
                return [
                    { value: 'contains',     label: 'has tag' },
                    { value: 'not_contains', label: 'does not have tag' },
                    { value: 'is_empty',     label: 'has no tags' },
                    { value: 'is_not_empty', label: 'has any tag' },
                ];
            }
            if (field === 'assigned_user') {
                return [
                    { value: 'is',           label: 'is' },
                    { value: 'is_not',       label: 'is not' },
                    { value: 'is_empty',     label: 'is unassigned' },
                    { value: 'is_not_empty', label: 'is assigned' },
                ];
            }
            return [
                { value: 'is',           label: 'is' },
                { value: 'is_not',       label: 'is not' },
                { value: 'is_empty',     label: 'is empty' },
                { value: 'is_not_empty', label: 'is not empty' },
            ];
        },

        getCustomField(id) {
            return this.customFields.find(f => f.id === id) ?? null;
        },

        addAction() {
            this.actions.push({ action_type: '', parameters: {} });
        },

        removeAction(ai) {
            this.actions.splice(ai, 1);
        },

        onActionTypeChange(action) {
            action.parameters = {};
        },

        serializeHidden() {
            // Flatten conditionGroups to flat list with group_index
            const flat = [];
            this.conditionGroups.forEach((group, gi) => {
                group.forEach(cond => {
                    flat.push({
                        group_index: gi,
                        field:       cond.field,
                        field_key:   cond.field_key ?? null,
                        operator:    cond.operator,
                        value:       (cond.value ?? []).filter(v => v !== '' && v !== null && v !== undefined),
                    });
                });
            });
            this.$refs.condJson.value = JSON.stringify(flat);
            this.$refs.actJson.value  = JSON.stringify(this.actions);
        },
    };
}
</script>
@endpush
@endsection
