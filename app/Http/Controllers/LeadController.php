<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CustomField;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadCustomValue;
use App\Models\LeadStatusHistory;
use App\Models\Pipeline;
use App\Models\Program;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\TagGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $pipelines = Pipeline::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $currentPipelineId = $request->get('pipeline', $pipelines->first()?->id);
        $authUser          = auth()->user();

        $filterableFields = CustomField::where('is_active', true)
            ->whereIn('type', ['select', 'multi_select'])
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        $filters = $this->parseFilters($request);

        $currentPipeline = $currentPipelineId
            ? Pipeline::with([
                'stages'       => fn ($q) => $q->orderBy('sort_order'),
                'stages.leads' => function ($q) use ($filters, $filterableFields) {
                    $this->applyLeadBaseFilters($q, $filters, $filterableFields);
                    $this->withLeadKanbanEagers($q);
                    $q->limit(100);
                },
            ])->find($currentPipelineId)
            : null;

        // Total counts per stage (for shown/total badge) — single grouped query
        $stageTotals = collect();
        if ($currentPipeline) {
            $stageIds = $currentPipeline->stages->pluck('id')->toArray();
            if ($stageIds) {
                $tq = Lead::whereIn('stage_id', $stageIds);
                $this->applyLeadBaseFilters($tq, $filters, $filterableFields);
                $stageTotals = $tq->selectRaw('stage_id, COUNT(*) as total')
                    ->groupBy('stage_id')
                    ->pluck('total', 'stage_id');
            }
        }

        $tagGroups     = TagGroup::with(['tags' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();
        $ungroupedTags = Tag::whereNull('tag_group_id')->orderBy('name')->get();
        $hasTags       = $tagGroups->contains(fn ($g) => $g->tags->isNotEmpty()) || $ungroupedTags->isNotEmpty();

        $internalUsers = $this->forceOwnLeads($authUser)
            ? collect([$authUser])
            : User::where(function ($q) {
                $q->whereNull('company_id')
                  ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
            })->orderBy('name')->get();

        $programsByCountry = Program::where('is_active', true)
            ->orderBy('country')->orderBy('name')
            ->get()->groupBy('country');

        $ownOnly = $this->forceOwnLeads($authUser);

        return view('leads.index', compact(
            'pipelines', 'currentPipeline', 'filters',
            'tagGroups', 'ungroupedTags', 'hasTags',
            'internalUsers', 'programsByCountry', 'ownOnly',
            'filterableFields', 'stageTotals'
        ));
    }

    public function create(Request $request): View
    {
        $pipelines = Pipeline::where('is_active', true)
            ->with(['stages' => fn ($q) => $q->orderBy('sort_order')->with(['subStages' => fn ($q) => $q->orderBy('sort_order')])])
            ->orderBy('sort_order')
            ->get();

        $users = User::where(function ($q) {
            $q->whereNull('company_id')
              ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
        })->orderBy('name')->get();

        $defaultPipelineId = $request->get('pipeline', $pipelines->first()?->id);
        $defaultStageId    = $request->get('stage');

        return view('leads.create', compact('pipelines', 'users', 'defaultPipelineId', 'defaultStageId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'          => ['required', 'string', 'max:100'],
            'last_name'           => ['nullable', 'string', 'max:100'],
            'email'               => ['nullable', 'email', 'max:191'],
            'phone'               => ['nullable', 'string', 'max:50'],
            'whatsapp'            => ['nullable', 'string', 'max:50'],
            'country_of_origin'   => ['nullable', 'string', 'max:100'],
            'nationality'         => ['nullable', 'string', 'max:100'],
            'language'            => ['nullable', 'string', 'max:50'],
            'pipeline_id'         => ['required', 'uuid', 'exists:pipelines,id'],
            'stage_id'            => ['required', 'uuid', 'exists:stages,id'],
            'sub_stage_id'        => ['nullable', 'uuid', 'exists:sub_stages,id'],
            'assigned_to'         => ['nullable', 'uuid', 'exists:users,id'],
            'potential_value'     => ['nullable', 'numeric', 'min:0'],
            'our_commission'      => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        $isDuplicate = false;
        if (!empty($validated['email']) || !empty($validated['phone'])) {
            $isDuplicate = Lead::where(function ($q) use ($validated) {
                if (!empty($validated['email'])) {
                    $q->orWhere('email', $validated['email']);
                }
                if (!empty($validated['phone'])) {
                    $q->orWhere('phone', $validated['phone']);
                }
            })->exists();
        }

        $validated['company_id']        = auth()->user()->company_id;
        $validated['is_duplicate_flag'] = $isDuplicate;

        $lead = Lead::create($validated);

        LeadStatusHistory::create([
            'lead_id'     => $lead->id,
            'changed_by'  => auth()->id(),
            'to_stage_id' => $lead->stage_id,
            'to_sub_stage_id' => $lead->sub_stage_id,
            'changed_at'  => now(),
        ]);

        $message = $isDuplicate
            ? 'Lead created — flagged as potential duplicate (matching email or phone found).'
            : 'Lead created.';

        return redirect()->route('leads.show', $lead)
            ->with($isDuplicate ? 'warning' : 'success', $message);
    }

    public function updateCustomValues(Request $request, Lead $lead): \Illuminate\Http\RedirectResponse
    {
        $fields = CustomField::where('is_active', true)->with('options')->get()->keyBy('key');

        $existingValues = LeadCustomValue::where('lead_id', $lead->id)
            ->get()
            ->keyBy('custom_field_id');

        $changes = [];

        foreach ($fields as $key => $field) {
            $raw = $request->input("custom.{$key}");

            if ($field->type === 'multi_select') {
                $values = array_values(array_filter((array) ($raw ?? [])));
                $value  = empty($values) ? null : json_encode($values);
            } elseif ($field->type === 'date') {
                $value = $raw ? trim($raw) : null;
                if ($value && !preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $value)) {
                    $value = null;
                }
            } else {
                $value = $raw ? trim($raw) : null;
            }

            $oldValue = $existingValues->get($field->id)?->value;

            if ($oldValue !== $value) {
                $optionLabels = $field->options->pluck('label', 'value');

                if ($field->type === 'multi_select') {
                    $oldArr = $oldValue ? (json_decode($oldValue, true) ?? []) : [];
                    $newArr = $value   ? (json_decode($value,    true) ?? []) : [];
                    $oldStr = $oldArr ? implode(', ', array_map(fn ($v) => $optionLabels[$v] ?? $v, $oldArr)) : '—';
                    $newStr = $newArr ? implode(', ', array_map(fn ($v) => $optionLabels[$v] ?? $v, $newArr)) : '—';
                } elseif ($field->type === 'select') {
                    $oldStr = $oldValue ? ($optionLabels[$oldValue] ?? $oldValue) : '—';
                    $newStr = $value    ? ($optionLabels[$value]    ?? $value)    : '—';
                } else {
                    $oldStr = $oldValue ?? '—';
                    $newStr = $value    ?? '—';
                }

                $changes[] = "{$field->label}: {$oldStr} → {$newStr}";
            }

            if ($value === null) {
                LeadCustomValue::where('lead_id', $lead->id)
                    ->where('custom_field_id', $field->id)
                    ->delete();
            } else {
                LeadCustomValue::updateOrCreate(
                    ['lead_id' => $lead->id, 'custom_field_id' => $field->id],
                    ['value' => $value]
                );
            }
        }

        if (!empty($changes)) {
            LeadActivity::create([
                'lead_id'     => $lead->id,
                'user_id'     => auth()->id(),
                'type'        => 'custom_field_updated',
                'description' => implode(' · ', $changes),
                'created_at'  => now(),
            ]);
        }

        return back()->with('success', 'Custom fields saved.');
    }

    public function show(Lead $lead): View
    {
        $user = auth()->user();

        // Internal non-admin users may only view leads assigned to them
        if ($this->forceOwnLeads($user)) {
            abort_unless($lead->assigned_to === $user->id, 403);
        }

        $lead->load([
            'pipeline', 'stage', 'subStage', 'assignedTo',
            'serviceProvider', 'agent',
            'statusHistory.changedBy',
            'statusHistory.fromStage',
            'statusHistory.toStage',
            'notes.createdBy',
            'tasks.assignedTo',
            'tasks.createdBy',
            'programs',
            'tags',
            'customValues.field.options',
            'activities.user',
        ]);

        // Build unified timeline: notes + tasks + activities, sorted oldest → newest
        $timelineItems = collect();

        foreach ($lead->notes as $note) {
            $timelineItems->push(['type' => 'note', 'sort_at' => $note->created_at, 'item' => $note]);
        }
        foreach ($lead->tasks as $task) {
            $timelineItems->push(['type' => 'task', 'sort_at' => $task->due_at ?? $task->created_at, 'item' => $task]);
        }
        foreach ($lead->activities as $activity) {
            $timelineItems->push(['type' => 'activity', 'sort_at' => $activity->created_at, 'item' => $activity]);
        }

        $sorted = $timelineItems->sortBy('sort_at')->values()->all();

        // Group consecutive WA messages of the same direction into a single bubble
        $grouped = collect();
        $i = 0;
        while ($i < count($sorted)) {
            $entry = $sorted[$i];
            $isWa  = $entry['type'] === 'activity'
                  && in_array($entry['item']->type, ['whatsapp_incoming', 'whatsapp_outgoing']);

            if ($isWa) {
                $waType   = $entry['item']->type;
                $messages = [$entry['item']];
                $j        = $i + 1;
                while ($j < count($sorted)
                    && $sorted[$j]['type'] === 'activity'
                    && $sorted[$j]['item']->type === $waType) {
                    $messages[] = $sorted[$j]['item'];
                    $j++;
                }
                $grouped->push([
                    'type'      => 'wa_group',
                    'sort_at'   => end($messages)->created_at,
                    'direction' => $waType,
                    'messages'  => $messages,
                ]);
                $i = $j;
            } else {
                $grouped->push($entry);
                $i++;
            }
        }

        $timeline = $grouped;

        // Mark unread incoming messages as read if the assigned user is viewing
        if (auth()->id() === $lead->assigned_to) {
            LeadActivity::where('lead_id', $lead->id)
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                    'read_by' => auth()->id(),
                ]);
        }

        $internalUsers = User::where(function ($q) {
            $q->whereNull('company_id')
              ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
        })->orderBy('name')->get();

        $serviceProviders = Company::where('type', 'service_provider')->orderBy('name')->get();
        $agents           = Company::where('type', 'agent')->orderBy('name')->get();

        $allTags = Tag::with('group')->orderBy('name')->get();

        $attachedProgramIds = $lead->programs->pluck('id');
        $availablePrograms  = Program::where('is_active', true)
            ->whereNotIn('id', $attachedProgramIds)
            ->orderBy('country')
            ->orderBy('name')
            ->get();

        $customFields      = CustomField::where('is_active', true)->with('options')->orderBy('sort_order')->get();
        $customValuesByKey = $lead->customValues->keyBy(fn ($cv) => $cv->field?->key);

        $waTemplates = \App\Models\WaTemplate::where('is_active', true)->orderBy('display_name')->orderBy('name')->get();

        $pipelines = Pipeline::with(['stages' => fn ($q) => $q->orderBy('sort_order')])->orderBy('sort_order')->get();

        $canManageAssignment     = $user->isInternalAdmin();
        $canChangeServiceProvider = $canManageAssignment || $lead->assigned_to === $user->id;

        return view('leads.show', compact(
            'lead', 'internalUsers', 'serviceProviders', 'agents', 'allTags', 'availablePrograms',
            'customFields', 'customValuesByKey', 'timeline', 'waTemplates', 'pipelines',
            'canManageAssignment', 'canChangeServiceProvider'
        ));
    }

    public function edit(Lead $lead): View
    {
        $pipelines = Pipeline::where('is_active', true)
            ->with(['stages' => fn ($q) => $q->orderBy('sort_order')->with(['subStages' => fn ($q) => $q->orderBy('sort_order')])])
            ->orderBy('sort_order')
            ->get();

        $users = User::where(function ($q) {
            $q->whereNull('company_id')
              ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
        })->orderBy('name')->get();

        return view('leads.edit', compact('lead', 'pipelines', 'users'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'          => ['required', 'string', 'max:100'],
            'last_name'           => ['nullable', 'string', 'max:100'],
            'email'               => ['nullable', 'email', 'max:191'],
            'phone'               => ['nullable', 'string', 'max:50'],
            'whatsapp'            => ['nullable', 'string', 'max:50'],
            'country_of_origin'   => ['nullable', 'string', 'max:100'],
            'nationality'         => ['nullable', 'string', 'max:100'],
            'language'            => ['nullable', 'string', 'max:50'],
            'pipeline_id'         => ['required', 'uuid', 'exists:pipelines,id'],
            'stage_id'            => ['required', 'uuid', 'exists:stages,id'],
            'sub_stage_id'        => ['nullable', 'uuid', 'exists:sub_stages,id'],
            'assigned_to'         => ['nullable', 'uuid', 'exists:users,id'],
            'potential_value'     => ['nullable', 'numeric', 'min:0'],
            'our_commission'      => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        $fromStageId    = $lead->stage_id;
        $fromSubStageId = $lead->sub_stage_id;

        $lead->update($validated);

        if ($fromStageId !== $lead->stage_id) {
            LeadStatusHistory::create([
                'lead_id'           => $lead->id,
                'changed_by'        => auth()->id(),
                'from_stage_id'     => $fromStageId,
                'to_stage_id'       => $lead->stage_id,
                'from_sub_stage_id' => $fromSubStageId,
                'to_sub_stage_id'   => $lead->sub_stage_id,
                'changed_at'        => now(),
            ]);
        }

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function assignUser(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $oldId = $lead->assigned_to;
        $newId = $validated['assigned_to'] ?? null;

        $lead->update(['assigned_to' => $newId]);

        if ($oldId !== $newId) {
            $oldName = $oldId ? User::find($oldId)?->name : null;
            $newName = $newId ? User::find($newId)?->name : null;

            LeadActivity::create([
                'lead_id'     => $lead->id,
                'user_id'     => auth()->id(),
                'type'        => 'assigned',
                'description' => $newName
                    ? 'Assigned to ' . $newName . ($oldName ? ' (was: ' . $oldName . ')' : '')
                    : 'Assignment removed' . ($oldName ? ' (was: ' . $oldName . ')' : ''),
                'visible_to'  => ['internal'],
                'created_at'  => now(),
            ]);
        }

        return back()->with('success', 'Assignment updated.');
    }

    public function assignCompany(Request $request, Lead $lead): RedirectResponse
    {
        $user = auth()->user();
        if ($request->input('field') === 'agent_id') {
            abort_unless($user->isInternalAdmin(), 403);
        } else {
            abort_unless($user->isInternalAdmin() || $lead->assigned_to === $user->id, 403);
        }

        $validated = $request->validate([
            'field'      => ['required', 'in:service_provider_id,agent_id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
        ]);

        $field = $validated['field'];
        $oldId = $lead->$field;
        $newId = $validated['company_id'] ?: null;

        $lead->update([$field => $newId]);

        if ($oldId !== $newId) {
            $isAgent = ($field === 'agent_id');
            $oldName = $oldId ? Company::find($oldId)?->name : null;
            $newName = $newId ? Company::find($newId)?->name : null;
            $label   = $isAgent ? 'Agent' : 'Service Provider';

            LeadActivity::create([
                'lead_id'     => $lead->id,
                'user_id'     => auth()->id(),
                'type'        => $isAgent ? 'agent_changed' : 'sp_changed',
                'description' => $newName
                    ? "{$label} set to {$newName}" . ($oldName ? " (was: {$oldName})" : '')
                    : "{$label} removed" . ($oldName ? " (was: {$oldName})" : ''),
                'visible_to'  => ['internal'],
                'created_at'  => now(),
            ]);
        }

        return back()->with('success', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $pipelineId = $lead->pipeline_id;
        $lead->delete();

        return redirect()->route('leads.index', ['pipeline' => $pipelineId])
            ->with('success', 'Lead deleted.');
    }

    public function move(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'stage_id' => ['required', 'uuid', 'exists:stages,id'],
        ]);

        if ($lead->stage_id === $validated['stage_id']) {
            return response()->json(['ok' => true]);
        }

        $fromStageId = $lead->stage_id;
        $fromStage   = Stage::find($fromStageId);
        $toStage     = Stage::find($validated['stage_id']);

        $lead->update([
            'stage_id'     => $validated['stage_id'],
            'sub_stage_id' => null,
        ]);

        LeadStatusHistory::create([
            'lead_id'       => $lead->id,
            'changed_by'    => auth()->id(),
            'from_stage_id' => $fromStageId,
            'to_stage_id'   => $validated['stage_id'],
            'changed_at'    => now(),
        ]);

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => auth()->id(),
            'type'        => 'stage_changed',
            'description' => 'Stage changed to ' . $toStage->name
                . ($fromStage ? ' (from: ' . $fromStage->name . ')' : ''),
            'visible_to'  => ['internal'],
            'created_at'  => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function kanbanCards(Request $request, Stage $stage): JsonResponse
    {
        $filterableFields = CustomField::where('is_active', true)
            ->whereIn('type', ['select', 'multi_select'])
            ->with('options')
            ->orderBy('sort_order')
            ->get();

        $filters = $this->parseFilters($request);
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 100;

        // Total count with filters
        $countQ = $stage->leads();
        $this->applyLeadBaseFilters($countQ, $filters, $filterableFields);
        $total = $countQ->count();

        // Paginated leads with all eager loads
        $leadsQ = $stage->leads();
        $this->applyLeadBaseFilters($leadsQ, $filters, $filterableFields);
        $this->withLeadKanbanEagers($leadsQ);
        $leads = $leadsQ->forPage($page, $perPage)->get();

        $html = '';
        foreach ($leads as $lead) {
            $html .= view('leads._kanban_card', compact('lead'))->render();
        }

        $shown = min(($page - 1) * $perPage + $leads->count(), $total);

        return response()->json([
            'html'      => $html,
            'total'     => $total,
            'shown'     => $shown,
            'next_page' => $shown < $total ? $page + 1 : null,
        ]);
    }

    // Internal non-admin users (role=member in an internal company) are restricted to their own leads.
    private function forceOwnLeads(User $user): bool
    {
        if ($user->isInternalAdmin()) return false;
        $company = $user->relationLoaded('company') ? $user->company : $user->load('company')->company;
        return $company?->type === 'internal';
    }

    private function parseFilters(Request $request): array
    {
        $authUser = auth()->user();
        $rawCf    = (array) $request->get('cf', []);
        return [
            'search'      => trim((string) $request->get('search')),
            'assigned_to' => $this->forceOwnLeads($authUser) ? $authUser->id : $request->get('assigned_to'),
            'source'      => $request->get('source'),
            'duplicate'   => $request->boolean('duplicate'),
            'program_id'  => $request->get('program_id'),
            'tags'        => array_values(array_filter((array) $request->get('tags', []))),
            'cf'          => array_filter($rawCf, fn ($v) => $v !== '' && $v !== null),
        ];
    }

    private function applyLeadBaseFilters($q, array $filters, $filterableFields)
    {
        return $q
            ->when($filters['tags'], fn ($q, $ids) =>
                $q->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $ids))
            )
            ->when($filters['search'], fn ($q, $s) =>
                $q->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                )
            )
            ->when($filters['assigned_to'], fn ($q, $uid) =>
                $q->where('assigned_to', $uid)
            )
            ->when($filters['source'] === 'meta_ad',
                fn ($q) => $q->where('source', 'meta_ad')
            )
            ->when($filters['source'] === 'manual',
                fn ($q) => $q->whereNull('source')->whereNull('agent_id')
            )
            ->when($filters['source'] === 'agent',
                fn ($q) => $q->whereNotNull('agent_id')
            )
            ->when($filters['duplicate'], fn ($q) =>
                $q->where('is_duplicate_flag', true)
            )
            ->when($filters['program_id'], fn ($q, $progId) =>
                str_starts_with($progId, 'country:')
                    ? $q->whereHas('programs', fn ($q) => $q->where('country', substr($progId, 8)))
                    : $q->whereHas('programs', fn ($q) => $q->where('programs.id', $progId))
            )
            ->when($filters['cf'], function ($q) use ($filters, $filterableFields) {
                foreach ($filters['cf'] as $key => $value) {
                    $field = $filterableFields->firstWhere('key', $key);
                    if (!$field) continue;
                    if ($field->type === 'multi_select') {
                        $q->whereHas('customValues', fn ($q2) =>
                            $q2->where('custom_field_id', $field->id)
                               ->where('value', 'like', '%"' . $value . '"%')
                        );
                    } else {
                        $q->whereHas('customValues', fn ($q2) =>
                            $q2->where('custom_field_id', $field->id)
                               ->where('value', $value)
                        );
                    }
                }
            });
    }

    private function withLeadKanbanEagers($q)
    {
        return $q
            ->withCount(['tasks as overdue_count' => fn ($q) => $q
                ->where('is_done', false)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
            ])
            ->withExists(['activities as has_wa_messages' => fn ($q) => $q
                ->whereIn('type', ['whatsapp_incoming', 'whatsapp_outgoing'])
            ])
            ->withExists(['activities as has_unread_wa' => fn ($q) => $q
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false)
            ])
            ->with([
                'assignedTo',
                'tags',
                'subStage',
                'programs' => fn ($q) => $q->wherePivot('is_primary', true),
            ])
            ->orderByDesc('has_unread_wa')
            ->orderByDesc('created_at');
    }
}
