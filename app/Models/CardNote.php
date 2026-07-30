<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardNote extends Model
{
    protected $fillable = ['card_id', 'content', 'created_by'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'card_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
