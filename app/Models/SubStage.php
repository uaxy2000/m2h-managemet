<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubStage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['stage_id', 'name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}
