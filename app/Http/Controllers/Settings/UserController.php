<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users     = User::with('company')->orderBy('name')->get();
        $companies = Company::orderBy('type')->orderBy('name')->get();

        return view('settings.users.index', compact('users', 'companies'));
    }

    public function create(): View
    {
        $companies = Company::orderBy('type')->orderBy('name')->get();

        return view('settings.users.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:191'],
            'email'                 => ['required', 'email', 'unique:users,email'],
            'role'                  => ['required', 'in:super_admin,admin,member'],
            'company_id'            => ['nullable', 'uuid', 'exists:companies,id'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        User::create($validated);

        return redirect()->route('settings.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        $companies = Company::orderBy('type')->orderBy('name')->get();

        return view('settings.users.edit', compact('user', 'companies'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:191'],
            'email'                 => ['required', 'email', 'unique:users,email,' . $user->id],
            'role'                  => ['required', 'in:super_admin,admin,member'],
            'company_id'            => ['nullable', 'uuid', 'exists:companies,id'],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);

        $user->update($validated);

        return redirect()->route('settings.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->user()?->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        try {
            $user->delete();
        } catch (\Throwable) {
            return back()->with('error', 'This user cannot be deleted — they have associated records.');
        }

        return redirect()->route('settings.users.index')->with('success', 'User deleted.');
    }
}
