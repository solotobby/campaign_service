<?php

namespace App\Console;

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
        'App\Console\Commands\LeaderboardRedemptionDailyCommand',
        'App\Console\Commands\LeaderboardRedemptionWeeklyCommand',
        'App\Console\Commands\LeaderboardRedemptionMonthlyCommand',
        'App\Console\Commands\LeaderboardRedemptionAllTimeCommand',
        'App\Console\Commands\ImportQuestionsOpentDbCommand',
        'App\Console\Commands\WeeklyLeaderboardCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('leaderboard:dailyredemption');
        // $schedule->command('questions:opentdbImport')->dailyAt('23:00');
        $schedule->command('leaderboard:weeklyleaderboardbroadcast')->everyMinute();//->weeklyOn(7, '22:00'); //pm every Sunday
        // $schedule->command('leaderboard:weeklyredemption')->weeklyOn(7, '20:30');
        // $schedule->command('leaderboard:monthlyredemption')->lastDayOfMonth('20:30');
        // $schedule->command('leaderboard:alltimeredemption')->dailyAt('20:10');
        $schedule->command('queue:work sqs --queue='.env('AWS_SQS_PAYOUT_QUEUE').' --sleep=3 --tries=3 --max-time=3600')->environments(['local', 'staging']);
    }
}
