<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TagGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TagGroupController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tag_groups,name'],
        ]);

        TagGroup::create($request->only('name'));

        return redirect()->route('settings.tags.index')->with('success', 'Group created.');
    }

    public function update(Request $request, TagGroup $tagGroup): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tag_groups,name,' . $tagGroup->id],
        ]);

        $tagGroup->update($request->only('name'));

        return redirect()->route('settings.tags.index')->with('success', 'Group renamed.');
    }

    public function destroy(TagGroup $tagGroup): RedirectResponse
    {
        $tagGroup->tags()->update(['tag_group_id' => null]);
        $tagGroup->delete();

        return redirect()->route('settings.tags.index')->with('success', 'Group deleted.');
    }
}
