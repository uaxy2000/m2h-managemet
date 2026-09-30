<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramPricing extends Model
{
    use HasUuids;

    protected $table = 'program_pricing';

    protected $fillable = [
        'program_id', 'service_provider_id', 'currency',
        'client_legal_fees', 'provider_share', 'partner_share',
        'commission_pct_legal_fees', 'commission_pct_investment',
        'effective_from',
    ];

    protected $casts = [
        'client_legal_fees'         => 'decimal:2',
        'provider_share'            => 'decimal:2',
        'partner_share'             => 'decimal:2',
        'commission_pct_legal_fees' => 'decimal:2',
        'commission_pct_investment' => 'decimal:2',
        'effective_from'            => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'service_provider_id');
    }

    /** Latest active pricing row for a given program + service provider combination */
    public static function latestFor(string $programId, string $serviceProviderId): ?self
    {
        return static::where('program_id', $programId)
            ->where('service_provider_id', $serviceProviderId)
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first();
    }
}
