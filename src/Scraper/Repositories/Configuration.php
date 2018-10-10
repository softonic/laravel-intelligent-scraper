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

    /**
     * Cache TTL in minutes.
     *
     * This is the time between config calculations.
     */
    const CACHE_TTL = 30;

    public function findByType(string $type): Collection
    {
        return ConfigurationModel::withType($type)->get();
    }

    public function calculate(string $type): Collection
    {
        $this->configurator = $this->configurator ?? resolve(Configurator::class);

        $cacheKey = $this->getCacheKey($type);
        $config   = Cache::get($cacheKey);
        if (!$config) {
            Log::warning('Calculating configuration');
            $scrapedDataset = ScrapedDataset::withType($type)->get();

            if ($scrapedDataset->isEmpty()) {
                throw new \UnexpectedValueException("A dataset example is needed to recalculate xpaths for type $type.");
            }

            $config = $this->configurator->configureFromDataset($scrapedDataset);
            Cache::put($cacheKey, $config, self::CACHE_TTL);
        }

        return $config;
    }

    protected function getCacheKey(string $type): string
    {
        return self::class . "-config-{$type}";
    }
}
