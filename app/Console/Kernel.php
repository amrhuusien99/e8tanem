<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\MakeUserCommand::class,
        \App\Console\Commands\PromoteUserToAdmin::class,
        \App\Console\Commands\CreateFilamentAdminCommand::class,
        \App\Console\Commands\FixFilamentUsersCommand::class,
        \App\Console\Commands\CleanupStorageCommand::class,
        \App\Console\Commands\DebugStorageCommand::class,
        \App\Console\Commands\StorageDebugCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
