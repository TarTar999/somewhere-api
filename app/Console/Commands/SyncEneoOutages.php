<?php

namespace App\Console\Commands;

use App\Services\EneoService;
use Illuminate\Console\Command;

class SyncEneoOutages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eneo:sync-outages
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize power outage programmes from ENEO API';

    /**
     * Execute the console command.
     */
    public function handle(EneoService $eneoService): int
    {
        $this->info('Starting ENEO outage programmes synchronization...');

        $startTime = microtime(true);

        try {
            $stats = $eneoService->syncProgrammes();

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info('Synchronization completed successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Programmes fetched', $stats['fetched']],
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Errors', $stats['errors']],
                    ['Old deleted', $stats['deleted_old'] ?? 0],
                    ['Duration', "{$duration}s"],
                ]
            );

            if ($stats['errors'] > 0) {
                $this->warn("There were {$stats['errors']} errors during sync. Check logs for details.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Synchronization failed: ' . $e->getMessage());
            $this->error('Check logs for more details.');

            return self::FAILURE;
        }
    }
}
