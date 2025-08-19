<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:promote-user-to-admin {email : The email of the user to promote to admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to admin status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }
        
        if ($user->role === 'admin') {
            $this->info("User {$email} is already an admin.");
            return 0;
        }
        
        $user->role = 'admin';
        $user->save();
        
        $this->info("User {$email} has been promoted to admin successfully.");
        return 0;
    }
}
