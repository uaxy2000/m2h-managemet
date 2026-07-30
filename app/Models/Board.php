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

    public function permissions(): HasMany
    {
        return $this->hasMany(BoardPermission::class);
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
        if ($user->isAdmin()) return true;
        $perm = $this->permissions->firstWhere('role', $user->role);
        return $perm?->can_read ?? false;
    }

    public function canWrite(User $user): bool
    {
        if ($user->isAdmin()) return true;
        $perm = $this->permissions->firstWhere('role', $user->role);
        return $perm?->can_write ?? false;
    }

    public function permissionSummary(): string
    {
        $roleLabels = [
            'member'               => 'User',
            'service_provider_user'=> 'Service Provider',
            'agent_user'           => 'Agent',
            'client'               => 'Client',
        ];

        $parts = [];
        foreach ($this->permissions as $p) {
            $label = $roleLabels[$p->role] ?? $p->role;
            if ($p->can_write) {
                $parts[] = "{$label}: read + write";
            } elseif ($p->can_read) {
                $parts[] = "{$label}: read only";
            }
        }

        return $parts ? implode(', ', $parts) : 'Admin only';
    }

    public function hasUnreadFor(User $user): bool
    {
        $read = $this->userReads->firstWhere('user_id', $user->id);
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
