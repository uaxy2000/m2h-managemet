<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    protected $fillable = [
        'meeting_id', 'participant_type', 'participant_id',
        'participant_name', 'participant_email', 'google_event_id', 'status',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
