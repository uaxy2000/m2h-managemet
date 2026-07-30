<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardCard extends Model
{
    protected $fillable = ['board_id', 'title', 'body', 'sort_order', 'created_by'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(CardPermission::class, 'card_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CardNote::class, 'card_id')->orderBy('created_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CardTask::class, 'card_id')->orderBy('due_at')->orderBy('id');
    }

    public function canRead(User $user): bool
    {
        if ($user->isAdmin()) return true;
        // Card-level override takes priority
        $cardPerm = $this->permissions->firstWhere('role', $user->role);
        if ($cardPerm !== null) return $cardPerm->can_read;
        // Fall back to board-level
        return $this->board->canRead($user);
    }

    public function canWrite(User $user): bool
    {
        if ($user->isAdmin()) return true;
        $cardPerm = $this->permissions->firstWhere('role', $user->role);
        if ($cardPerm !== null) return $cardPerm->can_write;
        return $this->board->canWrite($user);
    }
}
