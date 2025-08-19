<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand as FilamentMakeUserCommand;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class MakeUserCommand extends FilamentMakeUserCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:filament-user {name?} {email?} {--password=}';
    
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->option('password') ?? $this->generateRandomPassword();
        
        $user = $this->getUserModel()::create([
            'name' => $name ?: $this->ask('Name'),
            'email' => $email ?: $this->ask('Email address'),
            'password' => Hash::make($password),
            'role' => 'admin', // Set role to admin by default
        ]);

        $this->displayPassword($password);
        $this->sendSuccessMessage($user);

        return self::SUCCESS;
    }
    
    /**
     * Generate a random password.
     *
     * @return string
     */
    protected function generateRandomPassword(): string
    {
        return Str::password(8);
    }
    
    /**
     * Display the generated password.
     */
    protected function displayPassword(string $password): void
    {
        $this->info("Password: {$password}");
        $this->newLine();
        $this->info("Please save this password somewhere secure. You won't be able to see it again.");
        $this->newLine();
    }
}
