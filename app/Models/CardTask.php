<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardTask extends Model
{
    protected $fillable = ['card_id', 'title', 'assigned_to', 'due_at', 'is_done', 'created_by'];

    protected $casts = [
        'is_done' => 'boolean',
        'due_at'  => 'datetime',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'card_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
