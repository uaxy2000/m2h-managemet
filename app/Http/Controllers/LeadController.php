<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CustomField;
use App\Models\Lead;
use App\Models\LeadCustomValue;
use App\Models\LeadStatusHistory;
use App\Models\Pipeline;
use App\Models\Program;
use App\Models\Tag;
use App\Models\TagGroup;
use App\Models\User;
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

        $filters = [
            'search'      => trim((string) $request->get('search')),
            'assigned_to' => $authUser->role === 'user' ? $authUser->id : $request->get('assigned_to'),
            'source'      => $request->get('source'),
            'duplicate'   => $request->boolean('duplicate'),
            'program_id'  => $request->get('program_id'),
            'tags'        => array_values(array_filter((array) $request->get('tags', []))),
        ];

        $currentPipeline = $currentPipelineId
            ? Pipeline::with([
                'stages'       => fn ($q) => $q->orderBy('sort_order'),
                'stages.leads' => function ($q) use ($filters) {
                    $q->when($filters['tags'], fn ($q, $ids) =>
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
                        ->with([
                            'assignedTo',
                            'tags',
                            'programs' => fn ($q) => $q->wherePivot('is_primary', true),
                        ])
                        ->orderByDesc('created_at');
                },
            ])->find($currentPipelineId)
            : null;

        $tagGroups     = TagGroup::with(['tags' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();
        $ungroupedTags = Tag::whereNull('tag_group_id')->orderBy('name')->get();
        $hasTags       = $tagGroups->contains(fn ($g) => $g->tags->isNotEmpty()) || $ungroupedTags->isNotEmpty();

        $internalUsers = $authUser->role !== 'user'
            ? User::where(function ($q) {
                $q->whereNull('company_id')
                  ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
            })->orderBy('name')->get()
            : collect();

        $programsByCountry = Program::where('is_active', true)
            ->orderBy('country')->orderBy('name')
            ->get()->groupBy('country');

        return view('leads.index', compact(
            'pipelines', 'currentPipeline', 'filters',
            'tagGroups', 'ungroupedTags', 'hasTags',
            'internalUsers', 'programsByCountry'
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
        $fields = CustomField::where('is_active', true)->get()->keyBy('key');

        foreach ($fields as $key => $field) {
            $raw = $request->input("custom.{$key}");

            if ($field->type === 'multi_select') {
                $values = array_values(array_filter((array) ($raw ?? [])));
                $value  = empty($values) ? null : json_encode($values);
            } elseif ($field->type === 'date') {
                $value = $raw ? trim($raw) : null;
                // Accept YYYY, YYYY-MM, or YYYY-MM-DD
                if ($value && !preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $value)) {
                    $value = null;
                }
            } else {
                $value = $raw ? trim($raw) : null;
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

        return back()->with('success', 'Custom fields saved.');
    }

    public function show(Lead $lead): View
    {
        $lead->load([
            'pipeline', 'stage', 'subStage', 'assignedTo',
            'serviceProvider', 'agent',
            'statusHistory.changedBy',
            'statusHistory.fromStage',
            'statusHistory.toStage',
            'notes.createdBy',
            'tasks.assignedTo',
            'programs',
            'tags',
            'customValues.field.options',
        ]);

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

        $customFields     = CustomField::where('is_active', true)->with('options')->orderBy('sort_order')->get();
        $customValuesByKey = $lead->customValues->keyBy(fn ($cv) => $cv->field?->key);

        return view('leads.show', compact(
            'lead', 'internalUsers', 'serviceProviders', 'agents', 'allTags', 'availablePrograms',
            'customFields', 'customValuesByKey'
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
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $lead->update(['assigned_to' => $validated['assigned_to'] ?? null]);

        return back()->with('success', 'Assignment updated.');
    }

    public function assignCompany(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'field'      => ['required', 'in:service_provider_id,agent_id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
        ]);

        $lead->update([$validated['field'] => $validated['company_id'] ?: null]);

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

        return response()->json(['ok' => true]);
    }
}
