<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixFilamentUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-filament-users {--dry-run : Show how many users would be affected without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set role to admin for all existing Filament users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        // Find all users who might be Filament users but don't have admin role set
        $query = User::where('role', 'user');
        $count = $query->count();
        
        if ($isDryRun) {
            $this->info("Found {$count} user(s) that would be affected.");
            $this->table(
                ['ID', 'Name', 'Email'],
                $query->select('id', 'name', 'email')->get()->toArray()
            );
            return 0;
        }
        
        if ($count === 0) {
            $this->info("No users need fixing. All users have correct admin status.");
            return 0;
        }
        
        // Ask for confirmation before proceeding
        if (!$this->confirm("This will set the role to admin for {$count} user(s). Do you want to continue?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Get the list of users to display
        $users = $query->select('id', 'name', 'email')->get();
        
        // Show the list of users that will be updated
        $this->info('The following users will be granted admin privileges:');
        $this->table(['ID', 'Name', 'Email'], $users->toArray());
        
        // Make the updates
        $updated = $query->update(['role' => 'admin']);
        
        $this->info("Successfully updated {$updated} user(s) to have admin privileges.");
        return 0;
    }
}
