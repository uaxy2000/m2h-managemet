<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $fillable = ['title', 'description', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BoardMember::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BoardCard::class)->orderBy('sort_order')->orderBy('id');
    }

    public function userReads(): HasMany
    {
        return $this->hasMany(BoardUserRead::class);
    }

    public function canRead(User $user): bool
    {
        if ($this->isInternalAdmin($user)) return true;
        return $this->members->contains('user_id', $user->id);
    }

    public function canWrite(User $user): bool
    {
        if ($this->isInternalAdmin($user)) return true;
        $member = $this->members->firstWhere('user_id', $user->id);
        return $member?->can_write ?? false;
    }

    private function isInternalAdmin(User $user): bool
    {
        if (!$user->isAdmin()) return false;
        // Lazy-load company if needed
        $company = $user->relationLoaded('company') ? $user->company : $user->load('company')->company;
        return $company?->type === 'internal';
    }

    public function memberSummary(): string
    {
        if ($this->members->isEmpty()) return 'Admin only';
        $names = $this->members->map(fn ($m) => $m->user?->name ?? '?')->filter()->take(3)->join(', ');
        $extra = $this->members->count() > 3 ? ' +' . ($this->members->count() - 3) . ' more' : '';
        return $names . $extra;
    }

    public function hasUnreadFor(User $user): bool
    {
        $read  = $this->userReads->firstWhere('user_id', $user->id);
        $since = $read?->last_read_at;

        return $this->cards->some(function ($card) use ($user, $since) {
            $hasNotes = $card->notes
                ->where('created_by', '!=', $user->id)
                ->when($since, fn ($c) => $c->where('created_at', '>', $since))
                ->isNotEmpty();

            $hasTasks = $card->tasks
                ->where('created_by', '!=', $user->id)
                ->when($since, fn ($c) => $c->where('created_at', '>', $since))
                ->isNotEmpty();

            return $hasNotes || $hasTasks;
        });
    }
}
