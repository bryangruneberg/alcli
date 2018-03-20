<?php

namespace App\Providers;

use App\Jira;
use Illuminate\Support\ServiceProvider;

class JiraServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Jira::class, function ($app) {
            return new Jira(env('JIRA_URL'), env('JIRA_USERNAME'), env('JIRA_PASSWORD'));
        });
    }
}
