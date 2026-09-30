<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadCollaboratorController extends Controller
{
    public function update(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'collaborator_ids'   => ['nullable', 'array'],
            'collaborator_ids.*' => ['uuid', 'exists:users,id'],
        ]);

        $ids = $data['collaborator_ids'] ?? [];

        // Only internal users allowed
        $validIds = User::whereIn('id', $ids)
            ->whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->pluck('id')
            ->toArray();

        $lead->collaborators()->sync(
            collect($validIds)->mapWithKeys(fn ($id) => [$id => ['created_by' => auth()->id()]])->toArray()
        );

        return back()->with('success', 'Collaborators updated.');
    }
}
