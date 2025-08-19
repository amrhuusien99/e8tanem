<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\FileUpload;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Default string length for MySQL < 5.7.7 and MariaDB < 10.2.2
        Schema::defaultStringLength(191);
        
        // Make all users created by Filament's make:filament-user command admins
        User::created(function (User $user) {
            // Check if the user was created by the make:filament-user command
            if (app()->runningInConsole() && isset($_SERVER['argv']) && in_array('make:filament-user', $_SERVER['argv'])) {
                $user->update(['role' => 'admin']);
            }
        });

        // Add timestamp to all Filament file uploads
        FileUpload::configureUsing(function (FileUpload $fileUpload): void {
            $fileUpload->getUploadedFileNameForStorageUsing(
                function (FileUpload $component, string $fileName): string {
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $timestamp = now()->format('Y-m-d_H-i-s');
                    $basename = pathinfo($fileName, PATHINFO_FILENAME);
                    
                    // Create a filename with timestamp
                    return "{$basename}_{$timestamp}.{$extension}";
                }
            );
        });
    }
}
