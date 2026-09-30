<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'is_read', 'read_at', 'read_by', 'imap_message_id', 'created_at',
    ];

    protected $casts = [
        'visible_to' => 'array',
        'created_at' => 'datetime',
        'read_at'    => 'datetime',
        'is_read'    => 'boolean',
    ];

    // Ensures meta is always returned as array, even if DB has a plain JSON string value
    protected function meta(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_null($value)) return [];
                $decoded = is_string($value) ? json_decode($value, true) : $value;
                return is_array($decoded) ? $decoded : [];
            },
            set: fn ($value) => is_array($value) ? json_encode($value) : ($value ?? '[]'),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
