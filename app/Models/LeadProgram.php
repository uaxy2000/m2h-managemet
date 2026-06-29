<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadProgram extends Model
{
    use HasUuids;

    protected $table = 'lead_program';

    public $timestamps = false;

    protected $fillable = ['lead_id', 'program_id', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
