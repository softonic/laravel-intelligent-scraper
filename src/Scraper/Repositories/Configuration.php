<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Application\Configurator;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration as ConfigurationModel;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

class Configuration
{
    /**
     * @var Configurator
     */
    private $configurator;

    private $cacheKey = self::class . '-config';

    /**
     * Cache TTL in minutes.
     *
     * This is the time between config calculations.
     */
    const CACHE_TTL = 30;

    public function __construct(Configurator $crawler)
    {
        $this->configurator = $crawler;
    }

    public function findByType(string $type): Collection
    {
        return ConfigurationModel::withType($type)->get();
    }

    public function calculate(string $type): Collection
    {
        $config = Cache::get($this->cacheKey);
        if (!$config) {
            Log::warning('Calculating configuration');
            $scrapedDataset = ScrapedDataset::withType($type)->get();

            if ($scrapedDataset->isEmpty()) {
                throw new \UnexpectedValueException("A dataset example is needed to recalculate xpaths for type $type.");
            }

            $config = $this->configurator->configureFromDataset($scrapedDataset);
            Cache::put($this->cacheKey, $config, self::CACHE_TTL);
        }

        return $config;
    }
}
