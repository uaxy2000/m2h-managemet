<?php

namespace App\Console\Commands;

use App\Models\MetaInsight;
use App\Services\MetaAdsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncMetaInsights extends Command
{
    protected $signature   = 'meta:sync-insights {--date= : Date to sync (YYYY-MM-DD), defaults to yesterday}';
    protected $description = 'Sync Meta Ads insights into meta_insights table';

    public function handle(MetaAdsService $service): int
    {
        if (!$service->isConfigured()) {
            $this->error('META_ADS_ACCESS_TOKEN is not set in .env');
            return self::FAILURE;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : now()->subDay()->toDateString();

        $this->info("Syncing Meta Ads insights for {$date}...");

        try {
            $rows = $service->fetchDayInsights($date);
        } catch (\Throwable $e) {
            $this->error("API call failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (empty($rows)) {
            $this->warn('No data returned from Meta API.');
            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            MetaInsight::updateOrCreate(
                ['entity_id' => $row['entity_id'], 'date' => $row['date']],
                $row
            );
        }

        $this->info('Synced ' . count($rows) . ' rows.');

        return self::SUCCESS;
    }
}
