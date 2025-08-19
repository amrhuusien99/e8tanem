<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TestFilamentUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-filament-user {name} {email} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test creating a Filament user with admin role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $command = 'make:filament-user';
        if ($name) {
            $command .= ' --name="' . $name . '"';
        }
        if ($email) {
            $command .= ' --email="' . $email . '"';
        }
        if ($password) {
            $command .= ' --password="' . $password . '"';
        }
        
        $this->info("Running command: {$command}");
        
        Artisan::call($command);
        
        $this->info(Artisan::output());
        
        // Verify the user's role
        $this->info("Checking if user has admin role...");
        $user = \App\Models\User::where('email', $email)->first();
        
        if ($user) {
            $this->info("User role: " . $user->role);
            if ($user->role === 'admin') {
                $this->info("✅ User successfully created with admin role!");
            } else {
                $this->error("❌ User was created but does NOT have the admin role!");
            }
        } else {
            $this->error("User was not found in the database!");
        }
        
        return Command::SUCCESS;
    }
}
