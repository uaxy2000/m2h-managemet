<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaPage extends Model
{
    use HasUuids;

    protected $fillable = ['page_id', 'page_name', 'access_token', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function formMappings(): HasMany
    {
        return $this->hasMany(MetaFormMapping::class);
    }

    public function defaultMapping(): ?MetaFormMapping
    {
        return $this->formMappings()->where('is_default', true)->first();
    }
}
