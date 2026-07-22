<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use App\Models\MetaQuestionMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public function index(): View
    {
        $fields = CustomField::with(['options', 'metaQuestionMappings'])
            ->orderBy('sort_order')
            ->get();

        return view('settings.custom-fields.index', compact('fields'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key'        => ['required', 'string', 'max:80', 'alpha_dash', 'unique:custom_fields,key'],
            'label'      => ['required', 'string', 'max:100'],
            'type'       => ['required', 'in:date,text,select,multi_select'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? (CustomField::max('sort_order') + 1);

        CustomField::create($validated);

        return redirect()->route('settings.custom-fields.index')->with('success', 'Custom field created.');
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        $validated = $request->validate([
            'label'      => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $customField->update($validated);

        return back()->with('success', 'Field updated.')->with('open_field', $customField->id);
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->options()->delete();
        $customField->metaQuestionMappings()->delete();
        $customField->leadCustomValues()->delete();
        $customField->delete();

        return redirect()->route('settings.custom-fields.index')->with('success', 'Custom field deleted.');
    }

    // ── Options ───────────────────────────────────────────────────────

    public function storeOption(Request $request, CustomField $customField): RedirectResponse
    {
        $validated = $request->validate([
            'value'        => ['required', 'string', 'max:100'],
            'label'        => ['required', 'string', 'max:150'],
            'is_exclusive' => ['boolean'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['custom_field_id'] = $customField->id;
        $validated['sort_order']      = $validated['sort_order'] ?? ($customField->options()->max('sort_order') + 1);

        CustomFieldOption::create($validated);

        return back()->with('success', 'Option added.')->with('open_field', $customField->id);
    }

    public function updateOption(Request $request, CustomField $customField, CustomFieldOption $option): RedirectResponse
    {
        $validated = $request->validate([
            'label'        => ['required', 'string', 'max:150'],
            'is_exclusive' => ['boolean'],
            'meta_aliases' => ['nullable', 'string'],
        ]);

        // Parse textarea aliases (one per line) into a JSON array
        $aliases = null;
        if (!empty($validated['meta_aliases'])) {
            $aliases = array_values(array_filter(
                array_map('trim', explode("\n", str_replace("\r", '', $validated['meta_aliases'])))
            ));
        }

        $option->update([
            'label'        => $validated['label'],
            'is_exclusive' => $request->boolean('is_exclusive'),
            'meta_aliases' => $aliases,
        ]);

        return back()->with('success', 'Option updated.')->with('open_field', $customField->id);
    }

    public function destroyOption(CustomField $customField, CustomFieldOption $option): RedirectResponse
    {
        $option->delete();

        return back()->with('success', 'Option deleted.')->with('open_field', $customField->id);
    }

    // ── Meta question mappings ─────────────────────────────────────────

    public function storeMapping(Request $request, CustomField $customField): RedirectResponse
    {
        $validated = $request->validate([
            'meta_question_key' => ['required', 'string', 'max:500'],
        ]);

        $key = mb_strtolower(str_replace(['İ', 'I'], 'i', trim($validated['meta_question_key'])), 'UTF-8');

        // Replace existing mapping for this question key
        MetaQuestionMapping::where('meta_question_key', $key)->delete();

        MetaQuestionMapping::create([
            'meta_question_key' => $key,
            'custom_field_id'   => $customField->id,
        ]);

        return back()->with('success', 'Meta question mapped.')->with('open_field', $customField->id);
    }

    public function destroyMapping(MetaQuestionMapping $mapping): RedirectResponse
    {
        $fieldId = $mapping->custom_field_id;
        $mapping->delete();

        return back()->with('success', 'Mapping removed.')->with('open_field', $fieldId);
    }
}
