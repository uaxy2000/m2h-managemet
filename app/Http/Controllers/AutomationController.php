<?php

namespace App\Http\Controllers;

use App\Models\AutomationAction;
use App\Models\AutomationCondition;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\CustomField;
use App\Models\Lead;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\TagGroup;
use App\Models\User;
use App\Models\WaTemplate;
use App\Services\AutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function index(): View
    {
        $rules = AutomationRule::withCount('runs')
            ->with(['runs' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        // Recent conflicts (runs with status = conflict), most recent 20
        $conflicts = AutomationRuleRun::where('status', 'conflict')
            ->with(['rule', 'lead'])
            ->latest()
            ->limit(20)
            ->get();

        return view('automations.index', compact('rules', 'conflicts'));
    }

    public function create(): View
    {
        return view('automations.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:191',
            'description'     => 'nullable|string|max:500',
            'is_active'       => 'boolean',
            'priority'        => 'required|integer|min:1|max:999',
            're_run_mode'     => 'required|in:once,always',
            'trigger_events'  => 'required|array|min:1',
            'trigger_events.*'=> 'in:lead_created,lead_updated,manual',
            'conditions_json' => 'required|string',
            'actions_json'    => 'required|string',
        ]);

        $rule = AutomationRule::create([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'is_active'        => $request->boolean('is_active'),
            'priority'         => $validated['priority'],
            're_run_mode'      => $validated['re_run_mode'],
            'trigger_events'   => $validated['trigger_events'],
            'skip_duplicates'  => $request->boolean('skip_duplicates'),
        ]);

        $this->syncConditions($rule, $validated['conditions_json']);
        $this->syncActions($rule, $validated['actions_json']);

        return redirect()->route('automations.index')->with('success', 'Automation rule created.');
    }

    public function edit(AutomationRule $automation): View
    {
        $automation->load(['conditions', 'actions']);
        return view('automations.form', array_merge($this->formData(), ['rule' => $automation]));
    }

    public function update(Request $request, AutomationRule $automation): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:191',
            'description'     => 'nullable|string|max:500',
            'priority'        => 'required|integer|min:1|max:999',
            're_run_mode'     => 'required|in:once,always',
            'trigger_events'  => 'required|array|min:1',
            'trigger_events.*'=> 'in:lead_created,lead_updated,manual',
            'conditions_json' => 'required|string',
            'actions_json'    => 'required|string',
        ]);

        $automation->update([
            'name'            => $validated['name'],
            'description'     => $validated['description'] ?? null,
            'is_active'       => $request->boolean('is_active'),
            'priority'        => $validated['priority'],
            're_run_mode'     => $validated['re_run_mode'],
            'trigger_events'  => $validated['trigger_events'],
            'skip_duplicates' => $request->boolean('skip_duplicates'),
        ]);

        $this->syncConditions($automation, $validated['conditions_json']);
        $this->syncActions($automation, $validated['actions_json']);

        return redirect()->route('automations.index')->with('success', 'Automation rule updated.');
    }

    public function destroy(AutomationRule $automation): RedirectResponse
    {
        $automation->delete();
        return redirect()->route('automations.index')->with('success', 'Rule deleted.');
    }

    public function toggleActive(AutomationRule $automation): JsonResponse
    {
        $automation->update(['is_active' => !$automation->is_active]);
        return response()->json(['is_active' => $automation->is_active]);
    }

    public function preview(Request $request, AutomationRule $automation): JsonResponse
    {
        $automation->load('conditions');
        $result = AutomationService::preview($automation);
        return response()->json([
            'count'       => $result['total'],
            'already_ran' => $result['already_ran'],
            're_run_mode' => $automation->re_run_mode,
        ]);
    }

    public function run(Request $request, AutomationRule $automation): RedirectResponse
    {
        $leads = Lead::with(['tags', 'customValues.field'])->get();
        $count = 0;
        foreach ($leads as $lead) {
            if (AutomationService::evaluateConditions($lead, $automation)) {
                AutomationService::evaluate($lead, 'manual', auth()->id());
                $count++;
            }
        }
        return redirect()->route('automations.index')->with('success', "Rule applied to {$count} leads.");
    }

    private function formData(): array
    {
        $stages       = Stage::with('pipeline')->orderBy('sort_order')->get();
        $tagGroups    = TagGroup::with('tags')->orderBy('name')->get();
        $tags         = Tag::orderBy('name')->get();
        $customFields = CustomField::where('is_active', true)->with('options')->orderBy('sort_order')->get();
        $users        = User::where(function ($q) {
            $q->whereNull('company_id')
              ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
        })->orderBy('name')->get();
        $waTemplates  = WaTemplate::where('is_active', true)->orderBy('display_name')->get();

        return compact('stages', 'tagGroups', 'tags', 'customFields', 'users', 'waTemplates');
    }

    private function syncConditions(AutomationRule $rule, string $json): void
    {
        $rule->conditions()->delete();
        $conditions = json_decode($json, true) ?? [];
        foreach ($conditions as $c) {
            AutomationCondition::create([
                'rule_id'     => $rule->id,
                'group_index' => (int) ($c['group_index'] ?? 0),
                'field'       => $c['field'] ?? '',
                'field_key'   => $c['field_key'] ?? null,
                'operator'    => $c['operator'] ?? 'is',
                'value'       => $c['value'] ?? [],
            ]);
        }
    }

    private function syncActions(AutomationRule $rule, string $json): void
    {
        $rule->actions()->delete();
        $actions = json_decode($json, true) ?? [];
        foreach ($actions as $i => $a) {
            AutomationAction::create([
                'rule_id'     => $rule->id,
                'sort_order'  => $i,
                'action_type' => $a['action_type'] ?? '',
                'parameters'  => $a['parameters'] ?? [],
            ]);
        }
    }
}
