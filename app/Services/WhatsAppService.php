<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WaTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $phoneNumberId;
    private string $token;
    private string $apiBase = 'https://graph.facebook.com/v20.0';

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->token         = config('services.whatsapp.token');
    }

    public function sendText(Lead $lead, string $message, string|null $sentBy): bool
    {
        $to = preg_replace('/\D/', '', $lead->phone);

        $response = Http::withToken($this->token)
            ->post("{$this->apiBase}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp sendText failed', ['lead' => $lead->id, 'response' => $response->json()]);
            return false;
        }

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => $sentBy,
            'type'        => 'whatsapp_outgoing',
            'description' => $message,
            'visible_to'  => ['internal'],
            'meta'        => ['wa_message_id' => $response->json('messages.0.id')],
            'is_read'     => true,
        ]);

        return true;
    }

    public function sendTemplate(Lead $lead, WaTemplate $template, string|null $sentBy): bool
    {
        $to = preg_replace('/\D/', '', $lead->phone);
        if (!$to) {
            Log::warning('WhatsApp sendTemplate: lead has no phone', ['lead' => $lead->id]);
            return false;
        }

        // Load programs for resolving primary_program field
        $lead->loadMissing('programs');

        $components   = [];
        $paramFields  = $template->parameter_fields ?? [];

        // Header (image only)
        if ($template->headerType() === 'IMAGE' && $template->header_image_url) {
            $components[] = [
                'type'       => 'header',
                'parameters' => [['type' => 'image', 'image' => ['link' => $template->header_image_url]]],
            ];
        }

        // Body variables
        $bodyParams = collect($paramFields)
            ->where('component', 'body')
            ->sortBy('index')
            ->values();

        if ($bodyParams->isNotEmpty()) {
            $components[] = [
                'type'       => 'body',
                'parameters' => $bodyParams->map(fn ($p) => [
                    'type' => 'text',
                    'text' => $template->resolveField($p['field'] ?? '', $lead),
                ])->all(),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $template->name,
                'language'   => ['code' => $template->language],
                'components' => $components,
            ],
        ];

        $response = Http::withToken($this->token)
            ->post("{$this->apiBase}/{$this->phoneNumberId}/messages", $payload);

        if (!$response->successful()) {
            Log::error('WhatsApp sendTemplate failed', [
                'lead'     => $lead->id,
                'template' => $template->name,
                'response' => $response->json(),
            ]);
            return false;
        }

        $previewBody  = $template->resolveBodyPreview($lead);
        $templateLabel = $template->display_name ?? $template->name;

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => $sentBy ?: null,
            'type'        => 'whatsapp_outgoing',
            'description' => "[Şablon: {$templateLabel}]\n{$previewBody}",
            'visible_to'  => ['internal'],
            'meta'        => [
                'wa_message_id'   => $response->json('messages.0.id'),
                'template_name'   => $template->name,
            ],
            'is_read'     => true,
        ]);

        return true;
    }

    public function syncTemplates(): array
    {
        $wabaId   = config('services.whatsapp.template_waba_id');
        $response = Http::withToken($this->token)
            ->get("{$this->apiBase}/{$wabaId}/message_templates", [
                'fields' => 'name,language,category,status,components',
                'limit'  => 100,
            ]);

        if (!$response->successful()) {
            Log::error('WhatsApp syncTemplates failed', ['response' => $response->json()]);
            return ['synced' => 0, 'error' => $response->json('error.message', 'API error')];
        }

        $data    = $response->json('data', []);
        $synced  = 0;

        foreach ($data as $tpl) {
            if (strtoupper($tpl['status'] ?? '') !== 'APPROVED') continue;

            WaTemplate::updateOrCreate(
                ['name' => $tpl['name']],
                [
                    'language'   => $tpl['language'] ?? 'tr',
                    'category'   => $tpl['category'] ?? 'MARKETING',
                    'status'     => $tpl['status'] ?? 'APPROVED',
                    'components' => $tpl['components'] ?? [],
                    'is_active'  => true,
                    'synced_at'  => now(),
                ]
            );
            $synced++;
        }

        return ['synced' => $synced];
    }
}
