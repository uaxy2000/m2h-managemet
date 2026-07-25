<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadTagController extends Controller
{
    public function sync(Lead $lead, Request $request): RedirectResponse
    {
        $newIds  = collect($request->input('tag_ids', []))->filter()->values();
        $oldIds  = $lead->tags()->pluck('tags.id');

        $added   = $newIds->diff($oldIds);
        $removed = $oldIds->diff($newIds);

        if ($added->isEmpty() && $removed->isEmpty()) {
            return back()->withFragment('timeline');
        }

        $lead->tags()->sync($newIds->all());

        $addedNames   = Tag::whereIn('id', $added)->pluck('name');
        $removedNames = Tag::whereIn('id', $removed)->pluck('name');

        $parts = [];
        if ($addedNames->isNotEmpty()) {
            $parts[] = 'Added: ' . $addedNames->join(', ');
        }
        if ($removedNames->isNotEmpty()) {
            $parts[] = 'Removed: ' . $removedNames->join(', ');
        }

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => auth()->id(),
            'type'        => 'tags_updated',
            'description' => 'Tags updated — ' . implode(' · ', $parts),
            'visible_to'  => ['internal'],
        ]);

        return back()->withFragment('timeline');
    }

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
