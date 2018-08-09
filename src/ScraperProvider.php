<?php

namespace Softonic\LaravelIntelligentScraper;

use Illuminate\Support\ServiceProvider;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathBuilder;

class ScraperProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes(
            [__DIR__ . '/config/scraper.php' => config_path('scraper.php')],
            'config'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/config/scraper.php',
            'scraper'
        );

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Register any application services.
     *
     */
    public function register()
    {
        $this->app->when(XpathBuilder::class)
            ->needs('$idsToIgnore')
            ->give(function () {
                return config('scraper.xpath.ignore-identifiers');
            });
    }
}
