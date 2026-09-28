<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmailTemplateFile extends Model
{
    use HasUuids;

    protected $fillable = ['email_template_id', 'original_name', 'stored_name', 'mime_type', 'size'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function storagePath(): string
    {
        return 'email-template-files/' . $this->stored_name;
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->storagePath());
    }

    public function downloadUrl(): string
    {
        return route('settings.email-template-files.download', $this->id);
    }
}
