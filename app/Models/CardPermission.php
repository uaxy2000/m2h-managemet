<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardPermission extends Model
{
    public $timestamps = false;

    protected $fillable = ['card_id', 'role', 'can_read', 'can_write'];

    protected $casts = [
        'can_read'  => 'boolean',
        'can_write' => 'boolean',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'card_id');
    }
}
