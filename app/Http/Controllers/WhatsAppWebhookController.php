<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode      = $request->get('hub_mode');
        $token     = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        $payload = $request->json()->all();

        Log::info('WhatsApp webhook: payload received', ['object' => $payload['object'] ?? null]);

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response('OK', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    if (($message['type'] ?? '') !== 'text') {
                        continue;
                    }

                    $this->handleIncomingMessage(
                        from:    $message['from'],
                        text:    $message['text']['body'] ?? '',
                        waId:    $message['id'],
                        sentAt:  $message['timestamp'] ?? now()->timestamp,
                    );
                }
            }
        }

        return response('OK', 200);
    }

    private function handleIncomingMessage(string $from, string $text, string $waId, int $sentAt): void
    {
        $normalizedPhone = $this->normalizePhone($from);

        $lead = Lead::where('phone', $from)
            ->orWhere('phone', $normalizedPhone)
            ->orWhere('phone', '+' . $from)
            ->first();

        if (!$lead) {
            $lead = Lead::create([
                'first_name' => 'WA',
                'last_name'  => $from,
                'phone'      => '+' . $from,
                'source'     => 'whatsapp',
            ]);

            Log::info('WhatsApp webhook: new lead created from unknown number', [
                'phone' => $from, 'lead_id' => $lead->id,
            ]);
        }

        if (LeadActivity::where('meta->wa_message_id', $waId)->exists()) {
            return;
        }

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => null,
            'type'        => 'whatsapp_incoming',
            'description' => $text,
            'visible_to'  => ['internal'],
            'meta'        => ['wa_message_id' => $waId, 'from' => $from, 'sent_at' => $sentAt],
        ]);

        Log::info('WhatsApp webhook: message logged', ['lead_id' => $lead->id, 'from' => $from]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }
}
