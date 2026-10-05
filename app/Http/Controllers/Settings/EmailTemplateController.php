<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::with('files')->orderBy('name')->get();
        $variables = EmailTemplate::availableVariables();
        return view('settings.email-templates.index', compact('templates', 'variables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'subject'      => 'required|string|max:500',
            'body'         => 'required|string',
            'new_files'    => 'nullable|array|max:10',
            'new_files.*'  => 'file|max:20480',
        ]);

        $template = EmailTemplate::create([
            'name'      => $data['name'],
            'subject'   => $data['subject'],
            'body'      => $data['body'],
            'is_active' => true,
        ]);

        $this->storeFiles($template, $request->file('new_files', []));

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'subject'          => 'required|string|max:500',
            'body'             => 'required|string',
            'is_active'        => 'boolean',
            'visible_to_users' => 'boolean',
            'new_files'        => 'nullable|array|max:10',
            'new_files.*'      => 'file|max:20480',
        ]);

        $emailTemplate->update([
            'name'             => $data['name'],
            'subject'          => $data['subject'],
            'body'             => $data['body'],
            'is_active'        => $data['is_active'] ?? false,
            'visible_to_users' => $request->boolean('visible_to_users'),
        ]);

        $this->storeFiles($emailTemplate, $request->file('new_files', []));

        return back()->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        foreach ($emailTemplate->files as $file) {
            Storage::disk('local')->delete($file->storagePath());
        }
        $emailTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function destroyFile(EmailTemplateFile $emailTemplateFile)
    {
        Storage::disk('local')->delete($emailTemplateFile->storagePath());
        $emailTemplateFile->delete();
        return back()->with('success', 'File removed.');
    }

    public function downloadFile(EmailTemplateFile $emailTemplateFile)
    {
        $path = $emailTemplateFile->storagePath();
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }
        return response()->download(
            Storage::disk('local')->path($path),
            $emailTemplateFile->original_name,
            ['Content-Type' => $emailTemplateFile->mime_type]
        );
    }

    private function storeFiles(EmailTemplate $template, array $files): void
    {
        foreach (array_filter($files) as $file) {
            $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('local')->putFileAs('email-template-files', $file, $storedName);
            $template->files()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }
    }
}
