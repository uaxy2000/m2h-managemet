<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id', 'assigned_to', 'pipeline_id', 'stage_id', 'sub_stage_id',
        'first_name', 'last_name', 'email', 'phone', 'whatsapp',
        'country_of_origin', 'nationality', 'language',
        'potential_value', 'our_commission', 'expected_close_date',
        'service_provider_id', 'is_duplicate_flag',
    ];

    protected $casts = [
        'potential_value'     => 'decimal:2',
        'our_commission'      => 'decimal:2',
        'expected_close_date' => 'date',
        'is_duplicate_flag'   => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function subStage(): BelongsTo
    {
        return $this->belongsTo(SubStage::class, 'sub_stage_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class)->orderByDesc('changed_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->orderByDesc('created_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('is_done')->orderBy('due_at');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function initials(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return strtoupper(implode('', array_map(fn ($p) => substr($p, 0, 1), $parts)));
    }
}
