<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active', 'priority', 're_run_mode', 'trigger_events',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'trigger_events' => 'array',
        'priority'       => 'integer',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(AutomationCondition::class, 'rule_id')->orderBy('group_index')->orderBy('id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class, 'rule_id')->orderBy('sort_order');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRuleRun::class, 'rule_id')->latest();
    }

    public function hasTrigger(string $event): bool
    {
        return in_array($event, $this->trigger_events ?? []);
    }
}
