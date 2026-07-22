<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'user_id', 'type', 'description',
        'subject_type', 'subject_id', 'meta', 'visible_to',
    ];

    protected $casts = [
        'meta'       => 'array',
        'visible_to' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
