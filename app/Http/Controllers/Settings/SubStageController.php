<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use App\Models\SubStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubStageController extends Controller
{
    public function store(Request $request, Stage $stage): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $validated['stage_id']   = $stage->id;
        $validated['sort_order'] = ($stage->subStages()->max('sort_order') ?? -1) + 1;

        SubStage::create($validated);

        return redirect()->route('settings.pipelines.edit', $stage->pipeline_id)
            ->with('success', 'Sub-stage added.');
    }

    public function update(Request $request, Stage $stage, SubStage $subStage): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $subStage->update($validated);

        return redirect()->route('settings.pipelines.edit', $stage->pipeline_id)
            ->with('success', 'Sub-stage updated.');
    }

    public function destroy(Stage $stage, SubStage $subStage): RedirectResponse
    {
        try {
            $subStage->delete();
        } catch (\Throwable) {
            return back()->with('error', 'Cannot delete — this sub-stage is assigned to leads.');
        }

        return redirect()->route('settings.pipelines.edit', $stage->pipeline_id)
            ->with('success', 'Sub-stage deleted.');
    }

    public function sort(Request $request, Stage $stage): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array']]);

        foreach ($request->ids as $order => $id) {
            SubStage::where('id', $id)
                ->where('stage_id', $stage->id)
                ->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
