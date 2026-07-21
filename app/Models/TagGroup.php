<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagGroup extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['name'];

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class)->orderBy('name');
    }
}
