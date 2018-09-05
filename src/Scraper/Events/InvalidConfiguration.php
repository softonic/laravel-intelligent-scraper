<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Events;

class InvalidConfiguration
{
    /**
     * @var ScrapeRequest
     */
    public $scrapeRequest;

    public function __construct(ScrapeRequest $scrapeRequest)
    {
        $this->scrapeRequest = $scrapeRequest;
    }
}
