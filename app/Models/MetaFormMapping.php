<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaFormMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'meta_page_id', 'form_id', 'form_name', 'is_default',
        'pipeline_id', 'stage_id', 'tag_ids',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'tag_ids'    => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(MetaPage::class, 'meta_page_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}
