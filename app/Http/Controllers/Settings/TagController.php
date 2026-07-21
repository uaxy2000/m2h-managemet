<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\TagGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $groups = TagGroup::with('tags')->orderBy('name')->get();
        $ungrouped = Tag::whereNull('tag_group_id')->orderBy('name')->get();
        return view('settings.tags.index', compact('groups', 'ungrouped'));
    }

    public function create(): View
    {
        $groups = TagGroup::orderBy('name')->get();
        return view('settings.tags.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:50'],
            'color'        => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tag_group_id' => ['nullable', 'uuid', 'exists:tag_groups,id'],
        ]);

        Tag::create($validated);

        return redirect()->route('settings.tags.index')->with('success', 'Tag created.');
    }

    public function edit(Tag $tag): View
    {
        $groups = TagGroup::orderBy('name')->get();
        return view('settings.tags.edit', compact('tag', 'groups'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:50'],
            'color'        => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tag_group_id' => ['nullable', 'uuid', 'exists:tag_groups,id'],
        ]);

        $tag->update($validated);

        return redirect()->route('settings.tags.index')->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        try {
            $tag->delete();
        } catch (\Throwable) {
            return back()->with('error', 'Cannot delete — this tag is in use.');
        }

        return redirect()->route('settings.tags.index')->with('success', 'Tag deleted.');
    }
}
