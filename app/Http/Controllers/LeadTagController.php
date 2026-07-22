<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class LeadTagController extends Controller
{
    public function toggle(Lead $lead, Tag $tag): JsonResponse
    {
        $attached = $lead->tags()->where('tag_id', $tag->id)->exists();

        if ($attached) {
            $lead->tags()->detach($tag->id);
            $newType = 'tag_removed';
            $desc    = 'Tag removed: ' . $tag->name;
        } else {
            $lead->tags()->attach($tag->id);
            $newType = 'tag_added';
            $desc    = 'Tag added: ' . $tag->name;
        }

        // 5-second debounce: if a recent opposite activity exists for this tag, delete it (net-zero toggle)
        $recent = LeadActivity::where('lead_id', $lead->id)
            ->where('subject_type', 'tag')
            ->where('subject_id', $tag->id)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest('created_at')
            ->first();

        if ($recent) {
            $recent->delete();
        } else {
            LeadActivity::create([
                'lead_id'      => $lead->id,
                'user_id'      => auth()->id(),
                'type'         => $newType,
                'description'  => $desc,
                'subject_type' => 'tag',
                'subject_id'   => $tag->id,
                'visible_to'   => ['internal'],
            ]);
        }

        return response()->json(['active' => !$attached]);
    }
}
