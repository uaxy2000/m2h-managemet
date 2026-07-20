<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MetaFormMapping;
use App\Models\MetaPage;
use App\Models\Pipeline;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetaPageController extends Controller
{
    public function index(): View
    {
        $pages     = MetaPage::withCount('formMappings')
            ->with(['formMappings' => fn ($q) => $q->with(['pipeline', 'stage'])])
            ->orderBy('page_name')
            ->get();
        $pipelines = Pipeline::where('is_active', true)
            ->with(['stages' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
        $tags  = Tag::orderBy('name')->get();
        $users = User::where(function ($q) {
            $q->whereNull('company_id')
              ->orWhereHas('company', fn ($q) => $q->where('type', 'internal'));
        })->orderBy('name')->get();

        return view('settings.meta.index', compact('pages', 'pipelines', 'tags', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'page_id'      => ['required', 'string', 'max:64', 'unique:meta_pages,page_id'],
            'page_name'    => ['required', 'string', 'max:191'],
            'access_token' => ['required', 'string'],
        ]);

        MetaPage::create($validated);

        return redirect()->route('settings.meta.index')->with('success', 'Page connected.');
    }

    public function update(Request $request, MetaPage $metaPage): RedirectResponse
    {
        $validated = $request->validate([
            'page_name'    => ['required', 'string', 'max:191'],
            'access_token' => ['required', 'string'],
            'is_active'    => ['boolean'],
        ]);

        $metaPage->update($validated);

        return redirect()->route('settings.meta.index')->with('success', 'Page updated.');
    }

    public function destroy(MetaPage $metaPage): RedirectResponse
    {
        $metaPage->delete();

        return redirect()->route('settings.meta.index')->with('success', 'Page removed.');
    }

    // Form mappings
    public function storeMapping(Request $request, MetaPage $metaPage): RedirectResponse
    {
        $validated = $request->validate([
            'form_id'     => ['nullable', 'string', 'max:64'],
            'form_name'   => ['nullable', 'string', 'max:191'],
            'is_default'  => ['boolean'],
            'pipeline_id' => ['required', 'uuid', 'exists:pipelines,id'],
            'stage_id'    => ['required', 'uuid', 'exists:stages,id'],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['uuid', 'exists:tags,id'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        if (!empty($validated['is_default'])) {
            $metaPage->formMappings()->where('is_default', true)->update(['is_default' => false]);
        }

        $metaPage->formMappings()->create($validated);

        return redirect()->route('settings.meta.index')->with('success', 'Mapping saved.');
    }

    public function destroyMapping(MetaPage $metaPage, MetaFormMapping $mapping): RedirectResponse
    {
        abort_if($mapping->meta_page_id !== $metaPage->id, 403);

        $mapping->delete();

        return redirect()->route('settings.meta.index')->with('success', 'Mapping removed.');
    }
}
