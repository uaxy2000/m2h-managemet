<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaTemplate extends Model
{
    protected $fillable = [
        'name', 'display_name', 'language', 'category', 'status',
        'components', 'header_image_url', 'parameter_fields',
        'is_active', 'synced_at',
    ];

    protected $casts = [
        'components'       => 'array',
        'parameter_fields' => 'array',
        'is_active'        => 'boolean',
        'synced_at'        => 'datetime',
    ];

    public function bodyText(): string
    {
        foreach ($this->components ?? [] as $c) {
            if (strtoupper($c['type']) === 'BODY') {
                return $c['text'] ?? '';
            }
        }
        return '';
    }

    public function headerType(): ?string
    {
        foreach ($this->components ?? [] as $c) {
            if (strtoupper($c['type']) === 'HEADER') {
                return strtoupper($c['format'] ?? 'TEXT');
            }
        }
        return null;
    }

    public function bodyVariableCount(): int
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->bodyText(), $matches);
        return count(array_unique($matches[1] ?? []));
    }

    public function resolveBodyPreview(Lead $lead): string
    {
        $text   = $this->bodyText();
        $fields = $this->parameter_fields ?? [];

        foreach ($fields as $field) {
            if (($field['component'] ?? '') !== 'body') continue;
            $value = $this->resolveField($field['field'] ?? '', $lead);
            $text  = str_replace("{{{$field['index']}}}", $value, $text);
        }
        return $text;
    }

    public static function availableFields(): array
    {
        return [
            'first_name'              => 'Ad',
            'last_name'               => 'Soyad',
            'full_name'               => 'Ad Soyad',
            'phone'                   => 'Telefon',
            'email'                   => 'E-posta',
            'country_of_origin'       => 'Ülke/Uyruk',
            'primary_program'         => 'Program Adı',
            'primary_program_country' => 'Program Ülkesi',
        ];
    }

    public function resolveField(string $field, Lead $lead): string
    {
        return match($field) {
            'first_name'              => $lead->first_name ?? '',
            'last_name'               => $lead->last_name ?? '',
            'full_name'               => $lead->fullName(),
            'phone'                   => $lead->phone ?? '',
            'email'                   => $lead->email ?? '',
            'country_of_origin'       => $lead->country_of_origin ?? '',
            'primary_program'         => $lead->programs->first()?->name ?? '',
            'primary_program_country' => $lead->programs->first()?->country ?? '',
            default                   => '',
        };
    }
}
