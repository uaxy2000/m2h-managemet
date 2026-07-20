<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'whatsapp_number',
        'imap_host',
        'imap_user',
        'imap_pass_enc',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'imap_pass_enc',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function roleBadgeColor(): string
    {
        return match ($this->role) {
            'super_admin'          => 'bg-purple-100 text-purple-700',
            'admin'                => 'bg-blue-100 text-blue-700',
            'member'               => 'bg-green-100 text-green-700',
            'service_provider_user'=> 'bg-orange-100 text-orange-700',
            'agent_user'           => 'bg-gray-100 text-gray-600',
            default                => 'bg-gray-100 text-gray-600',
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'super_admin'          => 'Super Admin',
            'admin'                => 'Admin',
            'member'               => 'User',
            'service_provider_user'=> 'Service Provider',
            'agent_user'           => 'Agent',
            default                => $this->role,
        };
    }
}
