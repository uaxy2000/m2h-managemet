<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLead extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['payment_id', 'lead_id'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
