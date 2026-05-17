<?php

namespace App\Console\Commands;

use App\Models\ProofOfLocation;
use Illuminate\Console\Command;

class ExpireProofOfLocations extends Command
{
    protected $signature = 'proof:expire';

    protected $description = 'Mark expired proof of locations as expired';

    public function handle(): int
    {
        $count = ProofOfLocation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} proof(s) of location as expired.");

        return Command::SUCCESS;
    }
}
