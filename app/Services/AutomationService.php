<?php

namespace App\Services;

use App\Models\AutomationAction;
use App\Models\AutomationCondition;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadStatusHistory;
use App\Models\Tag;
use App\Models\User;
use App\Models\WaTemplate;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    /**
     * Evaluate all active rules against a lead for a given trigger event.
     * First matching rule runs its actions; subsequent matches are logged as conflicts.
     */
    public static function evaluate(Lead $lead, string $event): void
    {
        try {
            $rules = AutomationRule::where('is_active', true)
                ->orderBy('priority')
                ->orderBy('id')
                ->with(['conditions', 'actions'])
                ->get();

            $firstMatchId  = null;
            $conflictIds   = [];

            foreach ($rules as $rule) {
                if (!$rule->hasTrigger($event)) {
                    continue;
                }

                // once-per-lead check
                if ($rule->re_run_mode === 'once') {
                    $alreadyRan = AutomationRuleRun::where('rule_id', $rule->id)
                        ->where('lead_id', $lead->id)
                        ->where('status', 'success')
                        ->exists();
                    if ($alreadyRan) {
                        continue;
                    }
                }

                if (!static::evaluateConditions($lead, $rule)) {
                    continue;
                }

                if ($firstMatchId === null) {
                    $firstMatchId = $rule->id;
                    static::runRule($lead, $rule, $event);
                } else {
                    // Conflict: this rule also matched but won't run
                    $conflictIds[] = $rule->id;
                    AutomationRuleRun::create([
                        'rule_id'        => $rule->id,
                        'lead_id'        => $lead->id,
                        'triggered_by'   => $event,
                        'status'         => 'conflict',
                        'conflict_rules' => [$firstMatchId],
                        'actions_log'    => null,
                    ]);
                }
            }

            // If there were conflicts, notify admins and update the winning run's conflict list
            if ($firstMatchId !== null && !empty($conflictIds)) {
                AutomationRuleRun::where('rule_id', $firstMatchId)
                    ->where('lead_id', $lead->id)
                    ->latest()
                    ->first()
                    ?->update(['conflict_rules' => $conflictIds]);

                static::notifyConflicts($lead, $firstMatchId, $conflictIds, $event);
            }
        } catch (\Throwable $e) {
            Log::warning('AutomationService::evaluate failed', [
                'lead_id' => $lead->id, 'event' => $event, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Evaluate whether all condition groups pass for the given lead.
     * Groups are OR'd; conditions within a group are AND'd.
     */
    public static function evaluateConditions(Lead $lead, AutomationRule $rule): bool
    {
        $groups = $rule->conditions->groupBy('group_index');

        if ($groups->isEmpty()) {
            return false;
        }

        // Lazy-load needed relationships once
        $lead->loadMissing(['tags', 'customValues.field']);

        foreach ($groups as $conditions) {
            $groupPass = true;
            foreach ($conditions as $condition) {
                if (!static::evaluateCondition($lead, $condition)) {
                    $groupPass = false;
                    break;
                }
            }
            if ($groupPass) {
                return true;
            }
        }

        return false;
    }

    private static function evaluateCondition(Lead $lead, AutomationCondition $condition): bool
    {
        $operator = $condition->operator;
        $expected = $condition->value ?? []; // always an array from cast

        if ($operator === 'is_empty' || $operator === 'is_not_empty') {
            $fieldVal = static::getFieldValue($lead, $condition->field, $condition->field_key);
            return $operator === 'is_empty' ? empty($fieldVal) : !empty($fieldVal);
        }

        $fieldVal = static::getFieldValue($lead, $condition->field, $condition->field_key);

        return match ($operator) {
            'is'           => in_array($fieldVal, $expected),
            'is_not'       => !in_array($fieldVal, $expected),
            'in'           => in_array($fieldVal, $expected),
            'not_in'       => !in_array($fieldVal, $expected),
            'contains'     => !empty(array_intersect((array) $fieldVal, $expected)),
            'not_contains' => empty(array_intersect((array) $fieldVal, $expected)),
            default        => false,
        };
    }

    private static function getFieldValue(Lead $lead, string $field, ?string $fieldKey): mixed
    {
        return match ($field) {
            'stage'         => $lead->stage_id,
            'source'        => $lead->source,
            'assigned_user' => $lead->assigned_to,
            'country'       => $lead->country_of_origin,
            'tag'           => $lead->tags->pluck('id')->toArray(),
            'custom_field'  => static::getCustomFieldValue($lead, $fieldKey),
            default         => null,
        };
    }

    private static function getCustomFieldValue(Lead $lead, ?string $fieldKey): mixed
    {
        if (!$fieldKey) return null;
        $cv = $lead->customValues->first(fn ($v) => $v->custom_field_id === $fieldKey);
        if (!$cv) return null;
        return $cv->parsedValue();
    }

    /**
     * Run a matched rule's actions on a lead.
     */
    private static function runRule(Lead $lead, AutomationRule $rule, string $event): void
    {
        $actionsLog = [];

        foreach ($rule->actions as $action) {
            $result = static::runAction($lead, $action, $rule);
            $actionsLog[] = $result;
        }

        // Log lead activity
        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => null,
            'type'        => 'automation',
            'description' => 'Automation rule applied: ' . $rule->name,
            'meta'        => ['rule_id' => $rule->id, 'event' => $event, 'actions' => $actionsLog],
        ]);

        AutomationRuleRun::create([
            'rule_id'      => $rule->id,
            'lead_id'      => $lead->id,
            'triggered_by' => $event,
            'status'       => 'success',
            'actions_log'  => $actionsLog,
        ]);
    }

    private static function runAction(Lead $lead, AutomationAction $action, AutomationRule $rule): array
    {
        $params = $action->parameters ?? [];
        $log = ['type' => $action->action_type, 'ok' => false, 'note' => ''];

        try {
            switch ($action->action_type) {
                case 'assign_to':
                    $userId = $params['user_id'] ?? null;
                    if ($userId) {
                        $lead->update(['assigned_to' => $userId]);
                        $lead->refresh();
                        // Notify the newly assigned user
                        NotificationService::send(
                            userId: $userId,
                            type:   'lead_assigned',
                            title:  'Lead assigned to you',
                            body:   $lead->first_name . ' ' . $lead->last_name . ' assigned via automation: ' . $rule->name,
                            url:    route('leads.show', $lead->id),
                        );
                        $log['ok']   = true;
                        $log['note'] = 'Assigned to user ' . $userId;
                    }
                    break;

                case 'change_stage':
                    $stageId = $params['stage_id'] ?? null;
                    if ($stageId) {
                        $fromStageId = $lead->stage_id;
                        $lead->update(['stage_id' => $stageId]);
                        $lead->refresh();
                        LeadStatusHistory::create([
                            'lead_id'       => $lead->id,
                            'changed_by'    => null,
                            'from_stage_id' => $fromStageId,
                            'to_stage_id'   => $stageId,
                            'changed_at'    => now(),
                        ]);
                        $log['ok']   = true;
                        $log['note'] = 'Stage changed to ' . $stageId;
                    }
                    break;

                case 'send_wa':
                    $templateId = $params['template_id'] ?? null;
                    if ($templateId) {
                        $template = WaTemplate::find($templateId);
                        if ($template) {
                            $wa  = new \App\Services\WhatsAppService();
                            $ok  = $wa->sendTemplate($lead, $template, null);
                            $log['ok']   = $ok;
                            $log['note'] = $ok ? 'Sent template: ' . $template->name : 'WA send failed';
                        }
                    }
                    break;

                case 'add_tag':
                    $tagId = $params['tag_id'] ?? null;
                    if ($tagId && Tag::find($tagId)) {
                        $lead->tags()->syncWithoutDetaching([$tagId]);
                        $log['ok']   = true;
                        $log['note'] = 'Tag added: ' . $tagId;
                    }
                    break;

                case 'send_notification':
                    $toUserId = $lead->assigned_to;
                    if ($toUserId) {
                        NotificationService::send(
                            userId: $toUserId,
                            type:   'automation_notification',
                            title:  $params['title'] ?? $rule->name,
                            body:   $params['body'] ?? null,
                            url:    route('leads.show', $lead->id),
                        );
                        $log['ok']   = true;
                        $log['note'] = 'Notification sent to ' . $toUserId;
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $log['note'] = 'Error: ' . $e->getMessage();
            Log::warning('AutomationService action failed', [
                'rule_id' => $rule->id, 'action' => $action->action_type, 'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Notify all admin users about rule conflicts on a lead.
     */
    private static function notifyConflicts(Lead $lead, int $winnerRuleId, array $conflictIds, string $event): void
    {
        try {
            $adminIds = User::whereIn('role', ['super_admin', 'admin'])->pluck('id')->toArray();
            $winner   = AutomationRule::find($winnerRuleId);
            $names    = AutomationRule::whereIn('id', $conflictIds)->pluck('name')->join(', ');

            foreach ($adminIds as $adminId) {
                NotificationService::send(
                    userId: $adminId,
                    type:   'automation_conflict',
                    title:  'Automation rule conflict',
                    body:   'Rule "' . ($winner?->name ?? '?') . '" ran on ' . $lead->first_name . ' ' . $lead->last_name . '. Also matched: ' . $names,
                    url:    route('leads.show', $lead->id),
                );
            }
        } catch (\Throwable $e) {
            Log::warning('AutomationService::notifyConflicts failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Count how many leads currently match the rule's conditions (for preview).
     */
    public static function preview(AutomationRule $rule): int
    {
        $rule->loadMissing('conditions');

        if ($rule->conditions->isEmpty()) {
            return 0;
        }

        $leads = Lead::with(['tags', 'customValues.field'])->get();

        return $leads->filter(fn ($lead) => static::evaluateConditions($lead, $rule))->count();
    }
}
