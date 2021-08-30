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
        Commands\HistoriCronBKL::class,
        Commands\HistoriCronLV::class,
        Commands\HistoriCronPJM::class,
        Commands\HistoriCronPG::class,
        Commands\HistoriCronTL::class,
        Commands\HistoriCronSG::class,
        /*Commands\ReloadHistoriOld::class,*/
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('historibkl:cron')
                 ->everyMinute();

        $schedule->command('historilv:cron')
                 ->everyMinute();

        $schedule->command('historipjm:cron')
                 ->everyMinute();

        $schedule->command('historipg:cron')
                 ->everyMinute();

        $schedule->command('historitl:cron')
                 ->everyMinute();

        $schedule->command('historisg:cron')
                 ->everyMinute();

        /*$schedule->command('histori_old:cron')
                 ->everyMinute();*/
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
