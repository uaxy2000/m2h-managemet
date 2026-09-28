<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\MailerService;
use Illuminate\Http\Request;

class LeadEmailController extends Controller
{
    public function __construct(private MailerService $mailer) {}

    public function send(Request $request, Lead $lead)
    {
        $request->validate([
            'template_id'   => 'required|exists:email_templates,id',
            'subject'       => 'required|string|max:500',
            'extra_message' => 'nullable|string|max:2000',
        ]);

        if (!$lead->email) {
            return back()->with('email_error', 'This lead has no email address.');
        }

        if (!$this->mailer->isConfigured()) {
            return back()->with('email_error', 'SMTP not configured. Please set it up in Settings → Email.');
        }

        $template = EmailTemplate::findOrFail($request->template_id);
        $lead->loadMissing('programs');
        $resolved = $template->resolve($lead);

        $body = $resolved['body'];
        if ($request->filled('extra_message')) {
            $body .= "\n\n" . $request->extra_message;
        }

        try {
            $this->mailer->sendRaw(
                $lead->email,
                $lead->fullName(),
                $resolved['subject'],
                $body
            );
        } catch (\Exception $e) {
            return back()->with('email_error', 'Failed to send: ' . $e->getMessage());
        }

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => auth()->id(),
            'type'        => 'email_out',
            'description' => $resolved['subject'],
            'meta'        => [
                'to'              => $lead->email,
                'subject'         => $resolved['subject'],
                'body'            => $body,
                'template_name'   => $template->name,
                'extra_message'   => $request->extra_message,
            ],
        ]);

        return back()->with('email_success', 'Email sent to ' . $lead->email);
    }

    public function templates(Lead $lead)
    {
        $templates = EmailTemplate::where('is_active', true)->orderBy('name')->get();
        return response()->json($templates->map(fn ($t) => [
            'id'      => $t->id,
            'name'    => $t->name,
            'subject' => $t->subject,
            'body'    => $t->body,
        ]));
    }
}
