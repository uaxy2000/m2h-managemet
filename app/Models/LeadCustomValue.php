<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCustomValue extends Model
{
    use HasUuids;

    protected $fillable = ['lead_id', 'custom_field_id', 'value'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function parsedValue(): mixed
    {
        if ($this->field && $this->field->type === 'multi_select') {
            return json_decode($this->value ?? '[]', true) ?? [];
        }
        return $this->value;
    }
}
