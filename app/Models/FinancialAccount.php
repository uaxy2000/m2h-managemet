<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'type', 'currency', 'user_id', 'company_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AccountMovement::class, 'account_id');
    }

    public function balance(): float
    {
        return (float) $this->movements()->sum('amount');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'bank'             => 'Bank',
            'cash'             => 'Cash',
            'current_person'   => 'Person Account',
            'current_company'  => 'Company Account',
            default            => $this->type,
        };
    }

    // Find or create a current_person account for a user
    public static function forUser(User $user, string $currency = 'TRY'): self
    {
        return self::firstOrCreate(
            ['type' => 'current_person', 'user_id' => $user->id],
            ['name' => $user->name . ' Current', 'currency' => $currency, 'is_active' => true]
        );
    }

    // Find or create a current_company account for a company
    public static function forCompany(Company $company, string $currency = 'TRY'): self
    {
        return self::firstOrCreate(
            ['type' => 'current_company', 'company_id' => $company->id],
            ['name' => $company->name . ' Current', 'currency' => $currency, 'is_active' => true]
        );
    }
}
