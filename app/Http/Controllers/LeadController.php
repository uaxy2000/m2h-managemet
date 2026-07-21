<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Pipeline;
use App\Models\Program;
use App\Models\Tag;
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

        $currentPipeline = $currentPipelineId
            ? Pipeline::with([
                'stages' => function ($q) {
                    $q->orderBy('sort_order');
                },
                'stages.leads' => function ($q) use ($request) {
                    $q->when($request->get('tag'), fn ($q, $tagId) =>
                        $q->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId))
                    )->with([
                        'assignedTo',
                        'tags',
                        'programs' => fn ($q) => $q->wherePivot('is_primary', true),
                    ])->orderByDesc('created_at');
                },
            ])->find($currentPipelineId)
            : null;

        $tags        = Tag::orderBy('name')->get();
        $activeTagId = $request->get('tag');

        return view('leads.index', compact('pipelines', 'currentPipeline', 'tags', 'activeTagId'));
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

        return view('leads.show', compact(
            'lead', 'internalUsers', 'serviceProviders', 'agents', 'allTags', 'availablePrograms'
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
