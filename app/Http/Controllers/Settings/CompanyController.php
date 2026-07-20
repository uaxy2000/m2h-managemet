<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::withCount('users')->orderBy('type')->orderBy('name')->get();

        return view('settings.companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('settings.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:191'],
            'type'   => ['required', 'in:internal,service_provider,agent'],
            'domain' => ['nullable', 'string', 'max:191'],
        ]);

        Company::create($validated);

        return redirect()->route('settings.companies.index')->with('success', 'Şirket oluşturuldu.');
    }

    public function edit(Company $company): View
    {
        $users = $company->users()->orderBy('name')->get();

        return view('settings.companies.edit', compact('company', 'users'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:191'],
            'type'   => ['required', 'in:internal,service_provider,agent'],
            'domain' => ['nullable', 'string', 'max:191'],
        ]);

        $company->update($validated);

        return redirect()->route('settings.companies.index')->with('success', 'Şirket güncellendi.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->users()->exists()) {
            return back()->with('error', 'Bu şirkete bağlı kullanıcılar var, önce onları kaldırın.');
        }

        try {
            $company->delete();
        } catch (\Throwable) {
            return back()->with('error', 'Şirket silinemedi — ilişkili kayıtlar var.');
        }

        return redirect()->route('settings.companies.index')->with('success', 'Şirket silindi.');
    }
}
