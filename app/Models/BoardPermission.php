<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardPermission extends Model
{
    public $timestamps = false;

    protected $fillable = ['board_id', 'role', 'can_read', 'can_write'];

    protected $casts = [
        'can_read'  => 'boolean',
        'can_write' => 'boolean',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
