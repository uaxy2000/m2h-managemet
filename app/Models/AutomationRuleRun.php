<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRuleRun extends Model
{
    protected $fillable = [
        'rule_id', 'lead_id', 'triggered_by', 'status', 'conflict_rules', 'actions_log',
    ];

    protected $casts = [
        'conflict_rules' => 'array',
        'actions_log'    => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
