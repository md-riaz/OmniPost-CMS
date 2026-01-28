<?php

namespace App\Console\Commands;

use App\Jobs\IngestMetricsJob;
use Illuminate\Console\Command;

class IngestMetrics extends Command
{
    protected $signature = 'metrics:ingest {--days=30 : Number of days to look back for posts}';

    protected $description = 'Ingest metrics from social media platforms for published posts';

    public function handle()
    {
        $days = (int) $this->option('days');
        
        $this->info("Starting metrics ingestion for posts from the last {$days} days...");
        
        IngestMetricsJob::dispatch($days);
        
        $this->info('Metrics ingestion job dispatched successfully!');
        
        return 0;
    }
}
