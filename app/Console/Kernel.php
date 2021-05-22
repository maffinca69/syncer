<?php

namespace App\Console;

use App\Console\Commands\SyncCommand;
use App\Console\Commands\RefreshUserTokensCommand;
use App\Jobs\RefreshTokenJob;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncCommand::class,
        RefreshUserTokensCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sync:tracks')->everyMinute();
    }
}
