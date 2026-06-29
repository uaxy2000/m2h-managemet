<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'created_by', 'assigned_to',
        'title', 'description', 'due_at', 'is_done', 'created_at',
    ];

    protected $casts = [
        'due_at'     => 'datetime',
        'is_done'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOverdue(): bool
    {
        return !$this->is_done && $this->due_at->isPast();
    }
}
