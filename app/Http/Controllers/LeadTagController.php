<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class LeadTagController extends Controller
{
    public function toggle(Lead $lead, Tag $tag): JsonResponse
    {
        $attached = $lead->tags()->where('tag_id', $tag->id)->exists();

        if ($attached) {
            $lead->tags()->detach($tag->id);
        } else {
            $lead->tags()->attach($tag->id);
        }

        return response()->json(['active' => !$attached]);
    }
}
