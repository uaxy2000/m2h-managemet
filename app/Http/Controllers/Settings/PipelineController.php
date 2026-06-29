<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineController extends Controller
{
    public function index(): View
    {
        $pipelines = Pipeline::withCount('stages')->orderBy('sort_order')->get();

        return view('settings.pipelines.index', compact('pipelines'));
    }

    public function create(): View
    {
        return view('settings.pipelines.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $validated['sort_order'] = (Pipeline::max('sort_order') ?? -1) + 1;
        $validated['is_active']  = true;

        Pipeline::create($validated);

        return redirect()->route('settings.pipelines.index')
            ->with('success', 'Pipeline created.');
    }

    public function edit(Pipeline $pipeline): View
    {
        $pipeline->load(['stages.subStages']);

        return view('settings.pipelines.edit', compact('pipeline'));
    }

    public function update(Request $request, Pipeline $pipeline): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $pipeline->update($validated);

        return redirect()->route('settings.pipelines.edit', $pipeline)
            ->with('success', 'Pipeline saved.');
    }

    public function destroy(Pipeline $pipeline): RedirectResponse
    {
        try {
            $pipeline->delete();
        } catch (\Throwable) {
            return back()->with('error', 'Cannot delete — this pipeline has leads assigned to it.');
        }

        return redirect()->route('settings.pipelines.index')
            ->with('success', 'Pipeline deleted.');
    }

    public function sort(Request $request): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array']]);

        foreach ($request->ids as $order => $id) {
            Pipeline::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
