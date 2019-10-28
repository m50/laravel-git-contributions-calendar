<?php

namespace m50\GitCalendar\Providers;

use m50\GitCalendar\Services\GithubApi;
use m50\GitCalendar\Services\GitlabApi;
use Illuminate\Support\ServiceProvider;

class ContribCalendarServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/contrib-calendar.php',
            'contrib-calendar'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(GithubApi::class, function ($app) {
            return new GithubApi(
                config('contrib-calendar.github.username'),
                config('contrib-calendar.github.key'),
                config('contrib-calendar.github.url')
            );
        });
        $this->app->singleton(GitlabApi::class, function ($app) {
            return new GitlabApi(
                config('contrib-calendar.gitlab.key'),
                config('contrib-calendar.gitlab.url')
            );
        });
        $this->publishes([
            __DIR__ . '/../config/contrib-calendar.php' => config_path('contrib-calendar.php'),
        ], 'config');
        $this->publishes([
            __DIR__ . '/../resources/views' => base_path('resources/views/vendor/contribCalendar'),
        ], 'views');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'contribCalendar');
    }
}
