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

        $encryption = Setting::get('mail_encryption', 'tls');

        Config::set('mail.default',                  'smtp');
        Config::set('mail.mailers.smtp.host',        $host);
        Config::set('mail.mailers.smtp.port',        Setting::get('mail_port', '587'));
        Config::set('mail.mailers.smtp.encryption',  $encryption === 'none' ? null : $encryption);
        Config::set('mail.mailers.smtp.username',    Setting::get('mail_username'));
        Config::set('mail.mailers.smtp.password',    Setting::get('mail_password'));
        Config::set('mail.mailers.smtp.verify_peer', false);
        Config::set('mail.mailers.smtp.stream', [
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true],
        ]);
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

    public function sendRaw(string $to, string $toName, string $subject, string $body, array $attachments = []): void
    {
        $this->configure();

        $html = $this->wrapInTemplate(nl2br(e($body)));

        Mail::html($html, function ($m) use ($to, $toName, $subject, $attachments) {
            $m->to($to, $toName)->subject($subject);
            foreach ($attachments as $file) {
                $m->attach($file->getRealPath(), ['as' => $file->getClientOriginalName(), 'mime' => $file->getMimeType()]);
            }
        });
    }

    private function wrapInTemplate(string $bodyHtml): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>M2H</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          <tr>
            <td align="center" style="background:#ffffff;border-radius:12px 12px 0 0;padding:28px 40px;border-bottom:1px solid #e2e8f0;">
              <img src="https://m2h.ge/imgn/M2H-Logo-10.jpg" alt="M2H" width="120" style="display:block;border:0;max-width:120px;margin:0 auto;">
            </td>
          </tr>

          <tr>
            <td style="background:#ffffff;padding:36px 40px;color:#1e293b;font-size:15px;line-height:1.7;">
              {$bodyHtml}
            </td>
          </tr>

          <tr>
            <td style="background:#ffffff;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;">
              <p style="margin:0 0 6px;font-size:12px;">
                <a href="https://m2h.ge" style="color:#1e3a5f;text-decoration:none;font-weight:600;">m2h.ge</a>
                <span style="color:#94a3b8;">&nbsp;&bull;&nbsp;</span>
                <a href="https://www.instagram.com/goldenvisaservices" style="color:#1e3a5f;text-decoration:none;font-weight:600;">@goldenvisaservices</a>
              </p>
              <p style="margin:0;color:#94a3b8;font-size:11px;">
                &copy; M2H &mdash; Golden Visa Services
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
