<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function send(Lead $lead, Request $request): RedirectResponse
    {
        $request->validate(['message' => ['required', 'string', 'max:4096']]);

        $phone = preg_replace('/\D/', '', $lead->phone ?? '');

        if (!$phone) {
            return back()->with('wa_error', 'Lead has no phone number.');
        }

        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token         = config('services.whatsapp.token');

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => ['body' => $request->input('message')],
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp send failed', ['response' => $response->json(), 'lead_id' => $lead->id]);
            return back()->with('wa_error', 'Message could not be sent. Please try again.');
        }

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => auth()->id(),
            'type'        => 'whatsapp_outgoing',
            'description' => $request->input('message'),
            'visible_to'  => ['internal'],
            'meta'        => ['wa_message_id' => $response->json('messages.0.id') ?? null],
        ]);

        return back()->with('wa_success', 'Message sent.')->withFragment('timeline');
    }
}
