<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaQuestionMapping extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['meta_question_key', 'custom_field_id'];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }
}
