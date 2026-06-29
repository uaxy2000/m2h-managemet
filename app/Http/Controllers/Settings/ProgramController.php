<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::orderBy('country')->orderBy('name')->get();

        return view('settings.programs.index', compact('programs'));
    }

    public function create(): View
    {
        return view('settings.programs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country'        => ['required', 'string', 'max:100'],
            'name'           => ['required', 'string', 'max:191'],
            'type'           => ['required', 'in:' . implode(',', array_keys(Program::TYPES))],
            'min_investment' => ['nullable', 'numeric', 'min:0'],
            'currency'       => ['nullable', 'string', 'max:10'],
            'description'    => ['nullable', 'string', 'max:2000'],
        ]);

        Program::create(array_merge($validated, ['is_active' => true]));

        return redirect()->route('settings.programs.index')->with('success', 'Program created.');
    }

    public function edit(Program $program): View
    {
        return view('settings.programs.edit', compact('program'));
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'country'        => ['required', 'string', 'max:100'],
            'name'           => ['required', 'string', 'max:191'],
            'type'           => ['required', 'in:' . implode(',', array_keys(Program::TYPES))],
            'min_investment' => ['nullable', 'numeric', 'min:0'],
            'currency'       => ['nullable', 'string', 'max:10'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'is_active'      => ['boolean'],
        ]);

        $program->update(array_merge($validated, ['is_active' => $request->boolean('is_active')]));

        return redirect()->route('settings.programs.index')->with('success', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        try {
            $program->delete();
        } catch (\Throwable) {
            return back()->with('error', 'Cannot delete — this program is attached to leads.');
        }

        return redirect()->route('settings.programs.index')->with('success', 'Program deleted.');
    }
}
