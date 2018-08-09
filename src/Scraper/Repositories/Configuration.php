<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Repositories;

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

    public function __construct(Configurator $crawler)
    {
        $this->configurator = $crawler;
    }

    public function findByType(string $type)
    {
        return ConfigurationModel::withType($type)->get();
    }

    public function calculate(string $type)
    {
        Log::warning('Calculating configuration');
        $scrapedDataset = ScrapedDataset::withType($type)->get();

        if ($scrapedDataset->isEmpty()) {
            throw new \UnexpectedValueException("A dataset example is needed to recalculate xpaths for type $type.");
        }

        return $this->configurator->configureFromDataset($scrapedDataset);
    }
}
