<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\MetaFormMapping;
use App\Models\Tag;
use App\Models\TagGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Rule conditions/actions store tag ids in JSON (no FK); a deleted tag would silently change rule behaviour.
        // LIKE instead of JSON functions: production MariaDB lacks JSON_UNQUOTE.
        $ruleNames = AutomationRule::where(fn ($q) => $q
                ->whereHas('conditions', fn ($c) => $c->where('field', 'tag')->where('value', 'LIKE', '%' . $tag->id . '%'))
                ->orWhereHas('actions', fn ($a) => $a->where('parameters', 'LIKE', '%' . $tag->id . '%')))
            ->orderBy('name')
            ->pluck('name');

        if ($ruleNames->isNotEmpty()) {
            return back()->with('error', "Cannot delete \"{$tag->name}\" — it is used in automation rules: "
                . $ruleNames->implode(', ') . '. Remove it from these rules first.');
        }

        $leadCount = DB::table('lead_tags')->where('tag_id', $tag->id)->count();

        DB::transaction(function () use ($tag) {
            MetaFormMapping::where('tag_ids', 'LIKE', '%' . $tag->id . '%')->get()
                ->each(fn ($m) => $m->update(['tag_ids' => array_values(array_diff($m->tag_ids ?? [], [$tag->id]))]));

            $tag->delete(); // lead_tags rows cascade
        });

        return redirect()->route('settings.tags.index')
            ->with('success', "Tag \"{$tag->name}\" deleted" . ($leadCount ? " and removed from {$leadCount} lead(s)." : '.'));
    }
}
