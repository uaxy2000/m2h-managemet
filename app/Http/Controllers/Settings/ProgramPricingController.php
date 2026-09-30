<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Program;
use App\Models\ProgramPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProgramPricingController extends Controller
{
    public function store(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'service_provider_id'       => ['required', 'uuid', 'exists:companies,id'],
            'currency'                   => ['required', 'string', 'in:USD,EUR,GBP,TRY'],
            'client_legal_fees'          => ['required', 'numeric', 'min:0'],
            'provider_share'             => ['required', 'numeric', 'min:0'],
            'partner_share'              => ['required', 'numeric', 'min:0'],
            'commission_pct_legal_fees'  => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_pct_investment'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from'             => ['required', 'date'],
        ]);

        ProgramPricing::create(array_merge($validated, ['program_id' => $program->id]));

        return back()->with('success', 'Pricing added.');
    }

    public function destroy(Program $program, ProgramPricing $pricing): RedirectResponse
    {
        $pricing->delete();

        return back()->with('success', 'Pricing row deleted.');
    }
}
