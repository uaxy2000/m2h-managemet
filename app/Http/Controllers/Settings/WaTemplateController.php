<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\WaTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaTemplateController extends Controller
{
    public function index(): View
    {
        $templates = WaTemplate::orderBy('name')->get();
        return view('settings.wa-templates.index', compact('templates'));
    }

    public function sync(WhatsAppService $wa): RedirectResponse
    {
        $result = $wa->syncTemplates();

        if (isset($result['error'])) {
            return back()->with('error', 'Senkronizasyon hatası: ' . $result['error']);
        }

        return back()->with('success', "{$result['synced']} şablon senkronize edildi.");
    }

    public function update(Request $request, WaTemplate $waTemplate): RedirectResponse
    {
        $request->validate([
            'display_name'               => 'nullable|string|max:100',
            'header_image_url'           => 'nullable|url|max:1000',
            'parameter_fields'           => 'nullable|array',
            'parameter_fields.*.component' => 'required|string',
            'parameter_fields.*.index'   => 'required|integer',
            'parameter_fields.*.field'   => 'required|string',
            'is_active'                  => 'boolean',
        ]);

        $waTemplate->update([
            'display_name'     => $request->input('display_name'),
            'header_image_url' => $request->input('header_image_url'),
            'parameter_fields' => $request->input('parameter_fields'),
            'is_active'        => $request->boolean('is_active', true),
        ]);

        $label = $waTemplate->display_name ?? $waTemplate->name;
        return back()->with('success', "'{$label}' güncellendi.");
    }
}
