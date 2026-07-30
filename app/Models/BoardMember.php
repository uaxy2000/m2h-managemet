<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardMember extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['board_id', 'user_id', 'can_write'];

    protected $casts = ['can_write' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
