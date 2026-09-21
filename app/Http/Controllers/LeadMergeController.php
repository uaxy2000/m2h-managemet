<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadCustomValue;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadMergeController extends Controller
{
    public function merge(Request $request, Lead $lead): JsonResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $request->validate([
            'other_id'  => 'required|exists:leads,id',
            'direction' => 'required|in:other_into_this,this_into_other',
            'note'      => 'nullable|string|max:1000',
        ]);

        abort_if((string) $request->other_id === (string) $lead->id, 422, 'Cannot merge a lead with itself.');

        $other = Lead::findOrFail($request->other_id);

        // direction = who survives
        if ($request->direction === 'other_into_this') {
            $source = $other; // loses data, candidate for deletion
            $target = $lead;  // survives
        } else {
            $source = $lead;
            $target = $other;
        }

        $mergeNote = $request->filled('note') ? '<br><br><span class="text-gray-500">Note: ' . e($request->note) . '</span>' : '';

        DB::transaction(function () use ($source, $target, $mergeNote) {
            // Transfer activities
            LeadActivity::where('lead_id', $source->id)->update(['lead_id' => $target->id]);

            // Transfer notes
            Note::where('lead_id', $source->id)->update(['lead_id' => $target->id]);

            // Transfer custom values (only fields target doesn't already have)
            $existingFieldIds = $target->customValues()->pluck('custom_field_id')->toArray();
            LeadCustomValue::where('lead_id', $source->id)
                ->whereNotIn('custom_field_id', $existingFieldIds)
                ->update(['lead_id' => $target->id]);

            // Transfer tags
            $sourceTagIds = $source->tags()->pluck('tags.id')->toArray();
            $target->tags()->syncWithoutDetaching($sourceTagIds);

            $targetUrl   = route('leads.show', $target);
            $sourceName  = e($source->fullName());
            $targetName  = e($target->fullName());

            // Activity on target
            LeadActivity::create([
                'lead_id'     => $target->id,
                'user_id'     => auth()->id(),
                'type'        => 'merge',
                'description' => "Merged <strong>{$sourceName}</strong> into this lead. Activities, notes, custom fields and tags transferred.{$mergeNote}",
                'visible_to'  => 'all',
            ]);

            // Activity on source (so history is visible if not deleted)
            LeadActivity::create([
                'lead_id'     => $source->id,
                'user_id'     => auth()->id(),
                'type'        => 'merge',
                'description' => "Data from this lead was transferred to <a href=\"{$targetUrl}\" class=\"text-indigo-600 underline\">{$targetName}</a>.{$mergeNote}",
                'visible_to'  => 'all',
            ]);
        });

        return response()->json([
            'source_id'   => $source->id,
            'source_name' => $source->fullName(),
            'target_url'  => route('leads.show', $target),
        ]);
    }
}
