<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->loadMissing('company');
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'nullable|string|max:30',
            'whatsapp_number'  => 'nullable|string|max:30',
            'current_password' => 'nullable|string',
            'password'         => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($data['current_password'])) {
            if (!\Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
        }

        $update = [
            'name'            => $data['name'],
            'phone'           => $data['phone'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
        ];

        if (!empty($data['password'])) {
            $update['password'] = bcrypt($data['password']);
        }

        $user->update($update);

        return back()->with('success', 'Profile updated.');
    }
}
