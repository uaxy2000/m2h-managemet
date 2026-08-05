<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoList extends Model
{
    protected $fillable = ['title', 'description', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TodoListMember::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TodoListItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class, 'todo_list_boards');
    }

    public function canRead(User $user): bool
    {
        if ($user->isInternalAdmin()) return true;
        return $this->members->contains('user_id', $user->id);
    }

    public function memberSummary(): string
    {
        if ($this->members->isEmpty()) return 'Admin only';
        $names = $this->members->map(fn ($m) => $m->user?->name ?? '?')->filter()->take(3)->join(', ');
        $extra = $this->members->count() > 3 ? ' +' . ($this->members->count() - 3) . ' more' : '';
        return $names . $extra;
    }

    public function doneCount(): int
    {
        return $this->items->where('is_done', true)->count();
    }

    public function totalCount(): int
    {
        return $this->items->count();
    }
}
