<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('sort_order');
    }
}
