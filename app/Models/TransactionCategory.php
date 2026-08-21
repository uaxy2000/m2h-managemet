<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'direction', 'sort_order'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'category_id');
    }

    public function scopeExpenses($query)
    {
        return $query->where('direction', 'expense')->orderBy('sort_order')->orderBy('name');
    }

    public function scopeIncomes($query)
    {
        return $query->where('direction', 'income')->orderBy('sort_order')->orderBy('name');
    }
}
