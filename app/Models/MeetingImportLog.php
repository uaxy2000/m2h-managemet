<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingImportLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'action', 'meeting_id', 'lead_id', 'email_activity_id',
        'ical_uid', 'title', 'organizer',
        'old_start', 'new_start', 'new_end',
    ];

    protected $casts = [
        'old_start'  => 'datetime',
        'new_start'  => 'datetime',
        'new_end'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
