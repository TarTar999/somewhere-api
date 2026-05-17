<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ProcessScheduledAccountDeletions extends Command
{
    protected $signature = 'accounts:process-deletions';

    protected $description = 'Process scheduled account deletions';

    public function handle(): int
    {
        $users = User::whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $this->info("Processing deletion for user: {$user->email}");

            // Anonymize user data
            $user->update([
                'email' => "deleted_{$user->id}_{$user->email}",
                'phone' => "deleted_{$user->id}_{$user->phone}",
                'first_name' => 'Deleted',
                'last_name' => 'User',
            ]);

            // Revoke all tokens
            $user->tokens()->delete();
            $user->refreshTokens()->update(['revoked_at' => now()]);

            // Soft delete
            $user->delete();

            $count++;
        }

        $this->info("Processed {$count} account deletion(s).");

        return Command::SUCCESS;
    }
}
