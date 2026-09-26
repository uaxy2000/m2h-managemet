<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingRoom extends Model
{
    protected $fillable = ['name', 'capacity', 'location', 'color', 'is_active'];

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'room_id');
    }
}
