<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'subject', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function files(): HasMany
    {
        return $this->hasMany(EmailTemplateFile::class);
    }

    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'email_template_users', 'email_template_id', 'user_id');
    }

    public static function availableVariables(): array
    {
        return [
            '{{first_name}}'              => 'First Name',
            '{{last_name}}'               => 'Last Name',
            '{{full_name}}'               => 'Full Name',
            '{{email}}'                   => 'Email',
            '{{phone}}'                   => 'Phone',
            '{{primary_program}}'         => 'Primary Program',
            '{{primary_program_country}}' => 'Program Country',
        ];
    }

    public function resolve(Lead $lead): array
    {
        $map = [
            '{{first_name}}'              => $lead->first_name ?? '',
            '{{last_name}}'               => $lead->last_name ?? '',
            '{{full_name}}'               => $lead->fullName(),
            '{{email}}'                   => $lead->email ?? '',
            '{{phone}}'                   => $lead->phone ?? '',
            '{{primary_program}}'         => $lead->programs->first()?->name ?? '',
            '{{primary_program_country}}' => $lead->programs->first()?->country ?? '',
        ];

        return [
            'subject' => strtr($this->subject, $map),
            'body'    => strtr($this->body, $map),
        ];
    }
}
