<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AddUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:add {netID} {--admin : Grant admin privileges to the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new user to the system. Optionally grant admin privileges.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $netID = $this->argument('netID');
        $isAdmin = $this->option('admin');

        // Check if user already exists
        $existingUser = User::where('netID', $netID)->first();
        
        if ($existingUser) {
            $this->warn("User $netID already exists.");
            if ($this->confirm("Do you want to update their admin status?", false)) {
                $existingUser->isAdmin = $isAdmin;
                $existingUser->save();
                $this->info("User $netID updated. Admin status: " . ($isAdmin ? 'Yes' : 'No'));
            }
            return Command::SUCCESS;
        }

        // Create the new user
        $user = User::create([
            'netID' => $netID,
            'isAdmin' => $isAdmin,
        ]);

        $this->info("User $netID added successfully. Admin status: " . ($isAdmin ? 'Yes' : 'No'));

        return Command::SUCCESS;
    }
}

