<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'country', 'name', 'type', 'min_investment', 'currency', 'description', 'is_active',
    ];

    protected $casts = [
        'min_investment' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    const TYPES = [
        'investment'     => 'Investment',
        'real_estate'    => 'Real Estate',
        'company'        => 'Company Formation',
        'passive_income' => 'Passive Income',
        'digital_nomad'  => 'Digital Nomad',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function displayName(): string
    {
        return "{$this->country} — {$this->name}";
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_program')->withPivot('id', 'is_primary');
    }
}
