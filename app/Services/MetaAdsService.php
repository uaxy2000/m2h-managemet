<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsService
{
    private const BASE_URL = 'https://graph.facebook.com/v20.0/';
    private const INSIGHT_FIELDS = 'spend,impressions,clicks,cpm,cpc,ctr,actions';

    private string $token;
    private string $accountId;

    public function __construct()
    {
        $this->token     = (string) config('services.meta_ads.access_token');
        $this->accountId = (string) config('services.meta_ads.account_id');
    }

    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->accountId !== '';
    }

    /**
     * Fetch all insights for a given date at all levels (account, campaign, adset, ad).
     * Returns an array of upsert-ready rows for the meta_insights table.
     */
    public function fetchDayInsights(string $date): array
    {
        $rows = [];

        // Account level
        $accountData = $this->get("{$this->accountId}/insights", [
            'time_range' => json_encode(['since' => $date, 'until' => $date]),
            'fields'     => self::INSIGHT_FIELDS,
        ]);
        if (!empty($accountData['data'][0])) {
            $rows[] = $this->buildRow('account', $this->accountId, 'Account', null, $accountData['data'][0], $date);
        }

        // Campaign level
        $campaignRows = $this->fetchLevel('campaign', $date, [
            'campaign_id', 'campaign_name',
        ]);
        foreach ($campaignRows as $row) {
            $rows[] = $this->buildRow(
                'campaign',
                $row['campaign_id'],
                $row['campaign_name'] ?? null,
                $this->accountId,
                $row,
                $date
            );
        }

        // Adset level
        $adsetRows = $this->fetchLevel('adset', $date, [
            'adset_id', 'adset_name', 'campaign_id',
        ]);
        foreach ($adsetRows as $row) {
            $rows[] = $this->buildRow(
                'adset',
                $row['adset_id'],
                $row['adset_name'] ?? null,
                $row['campaign_id'] ?? null,
                $row,
                $date
            );
        }

        // Ad level
        $adRows = $this->fetchLevel('ad', $date, [
            'ad_id', 'ad_name', 'adset_id',
        ]);
        foreach ($adRows as $row) {
            $rows[] = $this->buildRow(
                'ad',
                $row['ad_id'],
                $row['ad_name'] ?? null,
                $row['adset_id'] ?? null,
                $row,
                $date
            );
        }

        return $rows;
    }

    private function fetchLevel(string $level, string $date, array $extraFields): array
    {
        $rows   = [];
        $params = [
            'level'      => $level,
            'time_range' => json_encode(['since' => $date, 'until' => $date]),
            'fields'     => implode(',', array_merge($extraFields, [self::INSIGHT_FIELDS])),
            'limit'      => 200,
        ];

        $endpoint = "{$this->accountId}/insights";

        do {
            $data = $this->get($endpoint, $params);
            $rows = array_merge($rows, $data['data'] ?? []);

            $next   = $data['paging']['next'] ?? null;
            $cursor = $data['paging']['cursors']['after'] ?? null;
            if ($next && $cursor) {
                $params['after'] = $cursor;
            } else {
                break;
            }
        } while (true);

        return $rows;
    }

    private function buildRow(
        string  $type,
        string  $entityId,
        ?string $entityName,
        ?string $parentId,
        array   $data,
        string  $date
    ): array {
        return [
            'entity_type'      => $type,
            'entity_id'        => $entityId,
            'entity_name'      => $entityName,
            'parent_entity_id' => $parentId,
            'date'             => $date,
            'spend'            => (float) ($data['spend'] ?? 0),
            'impressions'      => (int)   ($data['impressions'] ?? 0),
            'clicks'           => (int)   ($data['clicks'] ?? 0),
            'leads_count'      => $this->extractLeads($data['actions'] ?? []),
            'cpm'              => (float) ($data['cpm'] ?? 0),
            'cpc'              => (float) ($data['cpc'] ?? 0),
            'ctr'              => (float) ($data['ctr'] ?? 0),
            'synced_at'        => now()->toDateTimeString(),
        ];
    }

    private function extractLeads(array $actions): int
    {
        foreach ($actions as $action) {
            if (in_array($action['action_type'] ?? '', ['lead', 'onsite_conversion.lead_grouped'], true)) {
                return (int) ($action['value'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * Returns [transactions[], currency] where transactions are billing charges
     * from the Meta Ads account ordered newest first.
     */
    /**
     * Returns [transactions[], currency, rawError|null]
     */
    public function fetchBillingTransactions(int $limit = 100): array
    {
        $currency = $this->fetchAccountCurrency();

        // Fetch with minimal core fields; payment_option is not available on all accounts
        $response = $this->getRaw("{$this->accountId}/transactions", [
            'fields' => 'time_start,time_stop,amount,status',
            'limit'  => $limit,
        ]);

        // Surface API-level errors (Meta returns HTTP 200 with 'error' key in some cases)
        if (isset($response['error'])) {
            $msg = $response['error']['message'] ?? 'Unknown Meta API error';
            return [[], $currency, $msg];
        }

        $transactions = collect($response['data'] ?? [])
            ->map(fn($t) => [
                'id'         => $t['id'] ?? '',
                'date_start' => isset($t['time_start']) ? date('Y-m-d', (int)$t['time_start']) : '—',
                'date_stop'  => isset($t['time_stop'])  ? date('Y-m-d', (int)$t['time_stop'])  : '—',
                'amount'     => (float)($t['amount'] ?? 0),
                'status'     => $t['status'] ?? 'UNKNOWN',
            ])
            ->sortByDesc('date_stop')
            ->values()
            ->all();

        return [$transactions, $currency, null];
    }

    public function fetchAccountCurrency(): string
    {
        $response = $this->get($this->accountId, ['fields' => 'currency']);
        return $response['currency'] ?? 'USD';
    }

    /** Returns raw JSON including any 'error' key; never throws. */
    private function getRaw(string $endpoint, array $params): array
    {
        $params['access_token'] = $this->token;
        $response = Http::timeout(30)->get(self::BASE_URL . ltrim($endpoint, '/'), $params);
        $json = $response->json() ?? [];

        if (!$response->ok()) {
            Log::error('MetaAdsService: API error', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            // Preserve the error body so callers can surface it
            return $json ?: ['error' => ['message' => 'HTTP ' . $response->status()]];
        }

        return $json;
    }

    private function get(string $endpoint, array $params): array
    {
        $json = $this->getRaw($endpoint, $params);
        return isset($json['error']) ? [] : $json;
    }
}
