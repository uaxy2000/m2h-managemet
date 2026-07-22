<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldOption extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'custom_field_id', 'value', 'label', 'is_exclusive', 'meta_aliases', 'sort_order',
    ];

    protected $casts = [
        'is_exclusive' => 'boolean',
        'meta_aliases' => 'array',
        'sort_order'   => 'integer',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }
}
