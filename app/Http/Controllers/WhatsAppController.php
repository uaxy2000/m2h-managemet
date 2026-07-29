<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\WaTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function send(Lead $lead, Request $request, WhatsAppService $wa): RedirectResponse
    {
        $request->validate(['message' => ['required', 'string', 'max:4096']]);

        if (!$lead->phone) {
            return back()->with('wa_error', 'Lead has no phone number.');
        }

        $ok = $wa->sendText($lead, $request->input('message'), auth()->id());

        return $ok
            ? back()->with('wa_success', 'Mesaj gönderildi.')->withFragment('timeline')
            : back()->with('wa_error', 'Mesaj gönderilemedi. Lütfen tekrar deneyin.');
    }

    public function sendTemplate(Lead $lead, Request $request, WhatsAppService $wa): RedirectResponse
    {
        $request->validate(['template_id' => ['required', 'integer', 'exists:wa_templates,id']]);

        if (!$lead->phone) {
            return back()->with('wa_error', 'Lead has no phone number.');
        }

        $template = WaTemplate::findOrFail($request->integer('template_id'));
        $ok       = $wa->sendTemplate($lead, $template, auth()->id());

        return $ok
            ? back()->with('wa_success', "'{$template->display_name ?? $template->name}' gönderildi.")->withFragment('timeline')
            : back()->with('wa_error', 'Şablon gönderilemedi. Token süresi dolmuş olabilir.');
    }
}
