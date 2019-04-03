<?php

namespace Softonic\LaravelIntelligentScraper;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathBuilder;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ConfigurationScraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\InvalidConfiguration;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Softonic\LaravelIntelligentScraper\Scraper\Listeners\ConfigureScraper;
use Softonic\LaravelIntelligentScraper\Scraper\Listeners\Scrape;
use Softonic\LaravelIntelligentScraper\Scraper\Listeners\ScrapedListener;
use Softonic\LaravelIntelligentScraper\Scraper\Listeners\UpdateDataset;

class ScraperProvider extends EventServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ScrapeRequest::class        => [
            Scrape::class,
        ],
        InvalidConfiguration::class => [
            ConfigureScraper::class,
        ],
        Scraped::class              => [
            UpdateDataset::class,
            ScrapedListener::class,
        ],
        ConfigurationScraped::class => [
            UpdateDataset::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [];

    public function boot()
    {
        parent::boot();

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

        $this->app->when(ScrapedListener::class)
            ->needs('$listeners')
            ->give(function () {
                return config('scraper.listeners');
            });
    }
}
