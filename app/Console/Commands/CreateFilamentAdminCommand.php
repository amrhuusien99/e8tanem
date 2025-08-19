<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreateFilamentAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-filament-admin {name} {email} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Filament admin user with admin privileges';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');

        if (!$password) {
            $password = $this->secret('Please enter a password for the admin user');
            
            $passwordConfirmation = $this->secret('Please confirm the password');
            
            if ($password !== $passwordConfirmation) {
                $this->error('Passwords do not match!');
                return 1;
            }
        }

        // Validate the password
        $validator = validator(['password' => $password], [
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->error('The password does not meet the requirements:');
            foreach ($validator->errors()->all() as $error) {
                $this->line('- ' . $error);
            }
            return 1;
        }

        // Check if the user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($this->confirm("A user with the email {$email} already exists. Do you want to make this user an admin?")) {
                $existingUser->role = 'admin';
                $existingUser->save();
                $this->info("User {$email} has been updated to have admin privileges.");
                return 0;
            }
            $this->error("Operation cancelled.");
            return 1;
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin', // Set admin role
        ]);

        $this->info("Admin user {$email} created successfully.");
        return 0;
    }
}
