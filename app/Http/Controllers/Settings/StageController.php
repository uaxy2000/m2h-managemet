<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function store(Request $request, Pipeline $pipeline): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $validated['pipeline_id'] = $pipeline->id;
        $validated['sort_order']  = ($pipeline->stages()->max('sort_order') ?? -1) + 1;

        Stage::create($validated);

        return redirect()->route('settings.pipelines.edit', $pipeline)
            ->with('success', 'Stage added.');
    }

    public function update(Request $request, Pipeline $pipeline, Stage $stage): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $stage->update($validated);

        return redirect()->route('settings.pipelines.edit', $pipeline)
            ->with('success', 'Stage updated.');
    }

    public function destroy(Pipeline $pipeline, Stage $stage): RedirectResponse
    {
        try {
            $stage->delete();
        } catch (\Throwable) {
            return back()->with('error', 'Cannot delete — this stage has leads assigned to it.');
        }

        return redirect()->route('settings.pipelines.edit', $pipeline)
            ->with('success', 'Stage deleted.');
    }

    public function sort(Request $request, Pipeline $pipeline): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array']]);

        foreach ($request->ids as $order => $id) {
            Stage::where('id', $id)
                ->where('pipeline_id', $pipeline->id)
                ->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
