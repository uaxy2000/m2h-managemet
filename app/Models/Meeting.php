<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $fillable = [
        'title', 'description', 'start_at', 'end_at',
        'room_id', 'is_online', 'online_url', 'google_event_id', 'created_by',
    ];

    protected $casts = [
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
        'is_online' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function overlaps(string $startAt, string $endAt, ?int $excludeId = null): bool
    {
        $q = static::where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }
}
