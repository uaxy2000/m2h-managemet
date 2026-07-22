<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'label', 'type', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)->orderBy('sort_order');
    }

    public function leadCustomValues(): HasMany
    {
        return $this->hasMany(LeadCustomValue::class);
    }

    public function metaQuestionMappings(): HasMany
    {
        return $this->hasMany(MetaQuestionMapping::class);
    }

    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'multi_select'], true);
    }
}
