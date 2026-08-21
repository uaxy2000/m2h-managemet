<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_id', 'date', 'amount', 'description',
        'movable_type', 'movable_id', 'created_by',
    ];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }
}
