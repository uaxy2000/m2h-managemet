<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MailerService;
use Illuminate\Http\Request;

class EmailSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::whereIn('key', [
            'mail_host', 'mail_port', 'mail_encryption',
            'mail_username', 'mail_from_address', 'mail_from_name',
        ])->pluck('value', 'key');

        return view('settings.email.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mail_host'         => 'required|string|max:255',
            'mail_port'         => 'required|integer|min:1|max:65535',
            'mail_encryption'   => 'required|in:tls,ssl,none',
            'mail_username'     => 'required|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'required|string|max:255',
        ]);

        // Don't overwrite password if left blank
        if (empty($data['mail_password'])) {
            unset($data['mail_password']);
        }

        Setting::setMany($data);

        return back()->with('success', 'Email settings saved.');
    }

    public function test(Request $request, MailerService $mailer)
    {
        if (!$mailer->isConfigured()) {
            return back()->with('error', 'SMTP not configured yet.');
        }

        try {
            $mailer->sendRaw(
                auth()->user()->email,
                auth()->user()->name,
                'M2H CRM — SMTP Test',
                "This is a test email from M2H Management CRM.\n\nIf you received this, your SMTP settings are working correctly."
            );
            return back()->with('success', 'Test email sent to ' . auth()->user()->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Send failed: ' . $e->getMessage());
        }
    }
}
