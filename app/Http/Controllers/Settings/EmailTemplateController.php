<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates  = EmailTemplate::orderBy('name')->get();
        $variables  = EmailTemplate::availableVariables();
        return view('settings.email-templates.index', compact('templates', 'variables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body'    => 'required|string',
        ]);

        EmailTemplate::create($data + ['is_active' => true]);

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'subject'   => 'required|string|max:500',
            'body'      => 'required|string',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update($data);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }
}
