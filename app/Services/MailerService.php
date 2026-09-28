<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailerService
{
    public function configure(): void
    {
        $host = Setting::get('mail_host');
        if (!$host) return;

        Config::set('mail.mailers.smtp.host',       $host);
        Config::set('mail.mailers.smtp.port',        Setting::get('mail_port', '587'));
        Config::set('mail.mailers.smtp.encryption',  Setting::get('mail_encryption', 'tls'));
        Config::set('mail.mailers.smtp.username',    Setting::get('mail_username'));
        Config::set('mail.mailers.smtp.password',    Setting::get('mail_password'));
        Config::set('mail.from.address',             Setting::get('mail_from_address'));
        Config::set('mail.from.name',                Setting::get('mail_from_name', config('app.name')));
    }

    public function isConfigured(): bool
    {
        return (bool) Setting::get('mail_host');
    }

    public function send(string $to, string $toName, string $subject, string $body): void
    {
        $this->configure();

        Mail::html($body, function ($m) use ($to, $toName, $subject) {
            $m->to($to, $toName)->subject($subject);
        });
    }

    public function sendRaw(string $to, string $toName, string $subject, string $body): void
    {
        $this->configure();

        // Wrap plain text in a simple HTML layout to preserve formatting
        $html = nl2br(e($body));
        $html = "<html><body style='font-family:sans-serif;font-size:14px;line-height:1.6;color:#1e293b;'>{$html}</body></html>";

        Mail::html($html, function ($m) use ($to, $toName, $subject) {
            $m->to($to, $toName)->subject($subject);
        });
    }
}
