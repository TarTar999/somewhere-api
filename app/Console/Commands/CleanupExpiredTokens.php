<?php

namespace App\Console\Commands;

use App\Models\WebAccessToken;
use Illuminate\Console\Command;

class CleanupExpiredTokens extends Command
{
    protected $signature = 'tokens:cleanup';

    protected $description = 'Clean up expired web access tokens';

    public function handle(): int
    {
        $count = WebAccessToken::where('expires_at', '<', now())->delete();

        $this->info("Deleted {$count} expired web access token(s).");

        return Command::SUCCESS;
    }
}
