<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadProgram;
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

        return back()->with('success', 'Program added.');
    }

    public function setPrimary(Lead $lead, LeadProgram $leadProgram): RedirectResponse
    {
        abort_if($leadProgram->lead_id !== $lead->id, 404);

        LeadProgram::where('lead_id', $lead->id)->update(['is_primary' => false]);
        $leadProgram->update(['is_primary' => true]);

        return back()->with('success', 'Primary program updated.');
    }

    public function destroy(Lead $lead, LeadProgram $leadProgram): RedirectResponse
    {
        abort_if($leadProgram->lead_id !== $lead->id, 404);

        $wasPrimary = $leadProgram->is_primary;
        $leadProgram->delete();

        if ($wasPrimary) {
            LeadProgram::where('lead_id', $lead->id)->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Program removed.');
    }
}
