<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountTransfer extends Model
{
    use HasUuids;

    protected $fillable = [
        'date', 'from_account_id', 'to_account_id', 'amount', 'currency',
        'description', 'reference_type', 'reference_id', 'created_by',
    ];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(AccountMovement::class, 'movable');
    }
}
