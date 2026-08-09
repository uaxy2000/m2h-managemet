<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'amount', 'currency', 'lead_count', 'paid_at', 'note', 'created_by',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentLeads(): HasMany
    {
        return $this->hasMany(PaymentLead::class);
    }

    public function unitPrice(): float
    {
        return $this->lead_count > 0 ? round((float) $this->amount / $this->lead_count, 2) : 0;
    }
}
