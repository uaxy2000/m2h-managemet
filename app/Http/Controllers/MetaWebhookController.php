<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\MetaFormMapping;
use App\Models\MetaPage;
use App\Models\Tag;
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

        if (($payload['object'] ?? '') !== 'page') {
            return response('OK', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;
            $page   = MetaPage::where('page_id', $pageId)->where('is_active', true)->first();

            if (!$page) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
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

        // Parse name
        $fullName  = $fields['full_name'] ?? trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? ''));
        $nameParts = explode(' ', $fullName, 2);

        $lead = Lead::create([
            'first_name'       => $nameParts[0] ?? 'Unknown',
            'last_name'        => $nameParts[1] ?? null,
            'email'            => $fields['email'] ?? null,
            'phone'            => $fields['phone_number'] ?? $fields['phone'] ?? null,
            'country_of_origin'=> $fields['country'] ?? null,
            'pipeline_id'      => $mapping->pipeline_id,
            'stage_id'         => $mapping->stage_id,
            'company_id'       => $mapping->pipeline->company_id ?? null,
            'source'           => 'meta_ad',
            'meta_lead_id'     => $leadgenId,
            'meta_form_id'     => $formId,
            'meta_ad_name'     => $data['ad_name'] ?? null,
            'meta_campaign_name' => $data['campaign_name'] ?? null,
            'meta_platform'    => $data['platform'] ?? null,
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

        Log::info('Meta webhook: lead created', ['lead_id' => $lead->id, 'name' => $lead->fullName()]);
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
