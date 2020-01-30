<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeFailed;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScrapeFailedListener implements ShouldQueue
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
