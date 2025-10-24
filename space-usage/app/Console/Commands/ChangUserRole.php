<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ChangUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:admin {netID} {--remove : Remove admin privileges}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant or revoke admin privileges for a user. Creates the user if they don\'t exist.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $netID = $this->argument('netID');
        $remove = $this->option('remove');

        // Find or create the user
        $user = User::where('netID', $netID)->firstOrFail();

        // Update admin status
        if ($remove) {
            if (!$user->isAdmin) {
                $this->warn("User $netID is already not an admin.");
                return Command::SUCCESS;
            }
            $user->isAdmin = false;
            $user->save();
            $this->info("Admin privileges removed from user: $netID");
        } else {
            if ($user->isAdmin) {
                $this->warn("User $netID is already an admin.");
                return Command::SUCCESS;
            }
            $user->isAdmin = true;
            $user->save();
            
            $this->info("Admin privileges granted to user: $netID");
        }

        return Command::SUCCESS;
    }
}

