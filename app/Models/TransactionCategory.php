<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    use HasUuids;

    protected $fillable = ['parent_id', 'name', 'direction', 'sort_order'];

    // Groups (parent_id = null)
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function expenseRecords(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function incomeRecords(): HasMany
    {
        return $this->hasMany(Income::class, 'category_id');
    }

    // Only top-level groups (no parent)
    public function scopeGroups($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');
    }

    // Only leaf categories (have a parent)
    public function scopeLeaves($query)
    {
        return $query->whereNotNull('parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForExpenses($query)
    {
        return $query->where('direction', 'expense')->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForIncomes($query)
    {
        return $query->where('direction', 'income')->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');
    }
}
