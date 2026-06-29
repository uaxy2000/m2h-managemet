<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['pipeline_id', 'name', 'color', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    const COLORS = [
        '#6366f1', '#8b5cf6', '#ec4899', '#ef4444',
        '#f97316', '#eab308', '#22c55e', '#14b8a6',
        '#3b82f6', '#64748b',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function subStages(): HasMany
    {
        return $this->hasMany(SubStage::class)->orderBy('sort_order');
    }
}
