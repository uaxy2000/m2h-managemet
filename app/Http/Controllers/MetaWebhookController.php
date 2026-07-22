<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CustomFieldOption;
use App\Models\Lead;
use App\Models\LeadCustomValue;
use App\Models\LeadStatusHistory;
use App\Models\MetaFormMapping;
use App\Models\MetaPage;
use App\Models\MetaQuestionMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    // Meta webhook verification (GET)
    public function verify(Request $request): Response
    {
        $mode      = $request->get('hub_mode');
        $token     = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.meta.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // Receive lead events (POST)
    public function receive(Request $request): Response
    {
        // Verify signature
        $signature = $request->header('X-Hub-Signature-256');
        if (!$this->validSignature($request->getContent(), $signature)) {
            Log::warning('Meta webhook: invalid signature');
            return response('Forbidden', 403);
        }

        $payload = $request->json()->all();

        Log::info('Meta webhook: payload received', [
            'object'  => $payload['object'] ?? null,
            'entries' => count($payload['entry'] ?? []),
        ]);

        if (($payload['object'] ?? '') !== 'page') {
            Log::warning('Meta webhook: unexpected object type', ['object' => $payload['object'] ?? null]);
            return response('OK', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;
            $page   = MetaPage::where('page_id', $pageId)->where('is_active', true)->first();

            if (!$page) {
                Log::warning('Meta webhook: page not found or inactive', ['page_id' => $pageId]);
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
                    Log::info('Meta webhook: skipping non-leadgen change', ['field' => $change['field'] ?? null]);
                    continue;
                }

                $this->processLead($page, $change['value']);
            }
        }

        return response('OK', 200);
    }

    private function processLead(MetaPage $page, array $value): void
    {
        $leadgenId  = $value['leadgen_id'] ?? null;
        $formId     = $value['form_id'] ?? null;

        if (!$leadgenId) {
            return;
        }

        // Prevent duplicate imports
        if (Lead::where('meta_lead_id', $leadgenId)->exists()) {
            Log::info('Meta webhook: duplicate lead skipped', ['leadgen_id' => $leadgenId]);
            return;
        }

        // Fetch lead data from Graph API
        $response = Http::get("https://graph.facebook.com/v19.0/{$leadgenId}", [
            'access_token' => $page->access_token,
            'fields'       => 'id,created_time,field_data,platform,ad_id,ad_name,campaign_id,campaign_name,form_id',
        ]);

        if (!$response->ok()) {
            Log::error('Meta webhook: failed to fetch lead', ['leadgen_id' => $leadgenId, 'response' => $response->body()]);
            return;
        }

        $data   = $response->json();
        $fields = $this->parseFields($data['field_data'] ?? []);

        // Find mapping: form-specific first, then default
        $mapping = MetaFormMapping::where('meta_page_id', $page->id)
            ->where('form_id', $formId)
            ->first()
            ?? MetaFormMapping::where('meta_page_id', $page->id)
                ->where('is_default', true)
                ->first();

        if (!$mapping) {
            Log::warning('Meta webhook: no mapping found for form', ['form_id' => $formId, 'page_id' => $page->page_id]);
            return;
        }

        // Parse name — support both English and Turkish field names
        $standardKeys = [
            'full_name', 'adı_soyadı', 'adi_soyadi',
            'first_name', 'adı', 'adi',
            'last_name', 'soyadı', 'soyadi',
            'email', 'e-posta', 'eposta',
            'phone_number', 'phone', 'telefon_numarası', 'telefon_numarasi', 'telefon',
            'country', 'ülke', 'ulke',
        ];

        $fullName = $fields['full_name']
            ?? $fields['adı_soyadı'] ?? $fields['adi_soyadi']
            ?? trim(($fields['first_name'] ?? $fields['adı'] ?? $fields['adi'] ?? '')
                 . ' ' . ($fields['last_name'] ?? $fields['soyadı'] ?? $fields['soyadi'] ?? ''));
        $nameParts = explode(' ', trim($fullName), 2);

        $customFields = array_filter(
            $fields,
            fn ($key) => !in_array($key, $standardKeys, true),
            ARRAY_FILTER_USE_KEY
        );

        $companyId = Company::where('type', 'internal')->orderBy('created_at')->value('id');

        $lead = Lead::create([
            'first_name'        => $nameParts[0] ?: 'Unknown',
            'last_name'         => $nameParts[1] ?? null,
            'email'             => $fields['email'] ?? $fields['e-posta'] ?? $fields['eposta'] ?? null,
            'phone'             => $fields['phone_number'] ?? $fields['phone']
                                ?? $fields['telefon_numarası'] ?? $fields['telefon_numarasi'] ?? $fields['telefon'] ?? null,
            'country_of_origin' => $fields['country'] ?? $fields['ülke'] ?? $fields['ulke'] ?? null,
            'pipeline_id'       => $mapping->pipeline_id,
            'stage_id'          => $mapping->stage_id,
            'company_id'        => $companyId,
            'assigned_to'       => $mapping->assigned_to ?? null,
            'source'            => 'meta_ad',
            'meta_lead_id'      => $leadgenId,
            'meta_form_id'      => $formId,
            'meta_ad_name'      => $data['ad_name'] ?? null,
            'meta_campaign_name'=> $data['campaign_name'] ?? null,
            'meta_platform'     => $data['platform'] ?? null,
            'meta_form_data'    => !empty($customFields) ? $customFields : null,
        ]);

        LeadStatusHistory::create([
            'lead_id'     => $lead->id,
            'changed_by'  => $mapping->pipeline->company?->users()->where('role', 'super_admin')->first()?->id
                             ?? \App\Models\User::where('role', 'super_admin')->first()?->id,
            'to_stage_id' => $lead->stage_id,
            'changed_at'  => now(),
        ]);

        // Attach tags
        if (!empty($mapping->tag_ids)) {
            $lead->tags()->attach($mapping->tag_ids);
        }

        // Auto-populate custom fields from Meta form answers
        $this->populateCustomFields($lead, $customFields);

        Log::info('Meta webhook: lead created', ['lead_id' => $lead->id, 'name' => $lead->fullName()]);
    }

    private function populateCustomFields(Lead $lead, array $customFields): void
    {
        if (empty($customFields)) {
            return;
        }

        // Load all meta_question_mappings keyed by their normalized question key
        $questionMappings = MetaQuestionMapping::with('field.options')->get()
            ->keyBy('meta_question_key');

        foreach ($customFields as $rawKey => $rawValue) {
            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $normalizedKey = mb_strtolower(str_replace(['İ', 'I'], 'i', $rawKey), 'UTF-8');
            $questionMap   = $questionMappings[$normalizedKey] ?? null;

            if (!$questionMap || !$questionMap->field) {
                continue;
            }

            $field = $questionMap->field;

            // For select/multi_select: match raw value against option meta_aliases
            if (in_array($field->type, ['select', 'multi_select'], true)) {
                $normalizedRaw  = mb_strtolower(str_replace(['İ', 'I'], 'i', $rawValue), 'UTF-8');
                $matchedOption  = null;

                foreach ($field->options as $opt) {
                    $aliases = $opt->meta_aliases ?? [];
                    foreach ($aliases as $alias) {
                        if (mb_strtolower(str_replace(['İ', 'I'], 'i', $alias), 'UTF-8') === $normalizedRaw) {
                            $matchedOption = $opt;
                            break 2;
                        }
                    }
                }

                if (!$matchedOption) {
                    Log::info('Meta webhook: no option alias match', [
                        'field' => $field->key, 'raw_value' => $rawValue,
                    ]);
                    continue;
                }

                $storedValue = $field->type === 'multi_select'
                    ? json_encode([$matchedOption->value])
                    : $matchedOption->value;
            } else {
                // date / text: store raw value directly
                $storedValue = $rawValue;
            }

            LeadCustomValue::updateOrCreate(
                ['lead_id' => $lead->id, 'custom_field_id' => $field->id],
                ['value' => $storedValue]
            );
        }
    }

    private function parseFields(array $fieldData): array
    {
        $result = [];
        foreach ($fieldData as $item) {
            $result[$item['name']] = $item['values'][0] ?? null;
        }
        return $result;
    }

    private function validSignature(string $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, config('services.meta.app_secret'));
        return hash_equals($expected, $signature);
    }
}
