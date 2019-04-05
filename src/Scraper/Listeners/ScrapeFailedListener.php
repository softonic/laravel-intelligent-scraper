<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeFailed;

class ScrapeFailedListener
{
    private $listeners;

    public function __construct($listeners)
    {
        $this->listeners = $listeners;
    }

    public function handle(ScrapeFailed $scraped)
    {
        if (isset($this->listeners[$scraped->scrapeRequest->type])) {
            resolve($this->listeners[$scraped->scrapeRequest->type])->handle($scraped);
        }
    }
}
