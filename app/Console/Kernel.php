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
        'App\Console\Commands\JiraListIssuesCommand',
        'App\Console\Commands\JiraListUserWorklogsCommand',
        'App\Console\Commands\JiraListIssueWorklogsCommand',
        'App\Console\Commands\JiraListIssueCommentsCommand',
        'App\Console\Commands\JiraViewIssueCommand',
        'App\Console\Commands\JiraLogWorkIssueCommand',
        'App\Console\Commands\JiraCommentIssueCommand',
        'App\Console\Commands\JiraCreateIssueCommand',
        'App\Console\Commands\JiraTransitionIssueCommand',
        'App\Console\Commands\JiraGrabIssueCommand',
        'App\Console\Commands\JiraReleaseIssueCommand',
        'App\Console\Commands\JiraLabelIssueCommand',
        'App\Console\Commands\JiraUnlabelIssueCommand',
        'App\Console\Commands\CurrencyConvertCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
