<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImapSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::whereIn('key', [
            'imap_host', 'imap_port', 'imap_encryption',
            'imap_username', 'imap_enabled', 'imap_last_sync_at', 'imap_sync_token',
        ])->pluck('value', 'key');

        $users = User::whereNotNull('imap_host')
            ->orWhere('imap_enabled', true)
            ->orderBy('name')
            ->get();

        $allUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->orderBy('name')->get();

        return view('settings.email-imap.index', compact('settings', 'users', 'allUsers'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'imap_host'       => 'required|string|max:255',
            'imap_port'       => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'required|in:ssl,tls,none',
            'imap_username'   => 'required|string|max:255',
            'imap_password'   => 'nullable|string|max:255',
            'imap_enabled'    => 'boolean',
        ]);

        Setting::set('imap_host',       $data['imap_host']);
        Setting::set('imap_port',       $data['imap_port']);
        Setting::set('imap_encryption', $data['imap_encryption']);
        Setting::set('imap_username',   $data['imap_username']);
        Setting::set('imap_enabled',    $data['imap_enabled'] ?? '0');

        if (!empty($data['imap_password'])) {
            Setting::set('imap_password', encrypt($data['imap_password']));
        }

        // Generate sync token if not exists
        if (!Setting::get('imap_sync_token')) {
            Setting::set('imap_sync_token', Str::random(48));
        }

        return back()->with('success', 'Shared IMAP settings saved.');
    }

    public function regenerateToken()
    {
        Setting::set('imap_sync_token', Str::random(48));
        return back()->with('success', 'Sync token regenerated.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'imap_host'       => 'nullable|string|max:255',
            'imap_port'       => 'nullable|integer|min:1|max:65535',
            'imap_encryption' => 'nullable|in:ssl,tls,none',
            'imap_user'       => 'nullable|string|max:255',
            'imap_password'   => 'nullable|string|max:255',
            'imap_enabled'    => 'boolean',
        ]);

        $update = [
            'imap_host'       => $data['imap_host'] ?? null,
            'imap_port'       => $data['imap_port'] ?? 993,
            'imap_encryption' => $data['imap_encryption'] ?? 'ssl',
            'imap_user'       => $data['imap_user'] ?? null,
            'imap_enabled'    => $data['imap_enabled'] ?? false,
        ];

        if (!empty($data['imap_password'])) {
            $update['imap_pass_enc'] = encrypt($data['imap_password']);
        }

        $user->update($update);

        return back()->with('success', $user->name . ' IMAP settings saved.');
    }

    public function syncNow(EmailSyncService $sync)
    {
        $results = $sync->syncAll();
        $total   = collect($results)->sum('synced');
        return back()->with('success', "Sync complete — {$total} new email(s) added to timelines.");
    }
}
