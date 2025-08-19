<?php

namespace App\Providers;

use Filament\Commands\MakeUserCommand;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Override the Filament make:filament-user command to set role as 'admin'
        $this->app->resolving(MakeUserCommand::class, function ($command) {
            $command->afterCreate(function ($user) {
                // Set the role to 'admin' for any user created with make:filament-user
                $user->update(['role' => 'admin']);
            });
        });
    }
}
