<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadProgram;
use App\Models\ProgramPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadProgramController extends Controller
{
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'uuid', 'exists:programs,id'],
        ]);

        $exists = LeadProgram::where('lead_id', $lead->id)
            ->where('program_id', $validated['program_id'])
            ->exists();

        if ($exists) {
            return back()->with('program_error', 'This program is already attached to the lead.');
        }

        $isFirst = !LeadProgram::where('lead_id', $lead->id)->exists();

        LeadProgram::create([
            'lead_id'    => $lead->id,
            'program_id' => $validated['program_id'],
            'is_primary' => $isFirst,
        ]);

        $successMsg = 'Program added.';

        // Auto-fill deal values when this is the first (primary) program and SP is set
        if ($isFirst && $lead->service_provider_id) {
            $pricing = ProgramPricing::latestFor($validated['program_id'], $lead->service_provider_id);
            if ($pricing) {
                $lead->update([
                    'potential_value' => $pricing->client_legal_fees,
                    'our_commission'  => $pricing->partner_share,
                ]);
                $symbol = $pricing->currency === 'EUR' ? '€' : '$';
                $successMsg = "Program added. Deal auto-filled: {$symbol}" . number_format((float) $pricing->client_legal_fees) . " / commission {$symbol}" . number_format((float) $pricing->partner_share) . '.';
            }
        }

        return back()->with('success', $successMsg);
    }

    public function setPrimary(Lead $lead, LeadProgram $leadProgram): RedirectResponse
    {
        abort_if($leadProgram->lead_id !== $lead->id, 404);

        LeadProgram::where('lead_id', $lead->id)->update(['is_primary' => false]);
        $leadProgram->update(['is_primary' => true]);

        $successMsg = 'Primary program updated.';

        // Auto-fill deal values when primary program changes and SP is set
        $lead->refresh();
        if ($lead->service_provider_id) {
            $pricing = ProgramPricing::latestFor($leadProgram->program_id, $lead->service_provider_id);
            if ($pricing) {
                $lead->update([
                    'potential_value' => $pricing->client_legal_fees,
                    'our_commission'  => $pricing->partner_share,
                ]);
                $symbol = $pricing->currency === 'EUR' ? '€' : '$';
                $successMsg = "Primary program updated. Deal auto-filled: {$symbol}" . number_format((float) $pricing->client_legal_fees) . " / commission {$symbol}" . number_format((float) $pricing->partner_share) . '.';
            }
        }

        return back()->with('success', $successMsg);
    }

    public function destroy(Lead $lead, LeadProgram $leadProgram): RedirectResponse
    {
        abort_if($leadProgram->lead_id !== $lead->id, 404);

        $wasPrimary = $leadProgram->is_primary;
        $leadProgram->delete();

        $successMsg = 'Program removed.';

        if ($wasPrimary) {
            // Always clear deal values when primary is removed
            $lead->update(['potential_value' => null, 'our_commission' => null]);

            $next = LeadProgram::where('lead_id', $lead->id)->first();
            if ($next) {
                $next->update(['is_primary' => true]);

                // Auto-fill from new primary if SP is set
                $lead->refresh();
                if ($lead->service_provider_id) {
                    $pricing = ProgramPricing::latestFor($next->program_id, $lead->service_provider_id);
                    if ($pricing) {
                        $lead->update([
                            'potential_value' => $pricing->client_legal_fees,
                            'our_commission'  => $pricing->partner_share,
                        ]);
                        $symbol = $pricing->currency === 'EUR' ? '€' : '$';
                        $successMsg = 'Program removed. Deal auto-filled from new primary: ' . $symbol . number_format((float) $pricing->client_legal_fees) . ' / commission ' . $symbol . number_format((float) $pricing->partner_share) . '.';
                    } else {
                        $successMsg = 'Program removed. Deal values cleared (no pricing found for new primary).';
                    }
                } else {
                    $successMsg = 'Program removed. Deal values cleared.';
                }
            } else {
                $successMsg = 'Program removed. Deal values cleared.';
            }
        }

        return back()->with('success', $successMsg);
    }
}
