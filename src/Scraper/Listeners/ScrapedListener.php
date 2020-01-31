<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;

class ScrapedListener implements ShouldQueue
{
    private $listeners;

    public function __construct($listeners)
    {
        $this->listeners = $listeners;
    }

    public function handle(Scraped $scraped)
    {
        if (isset($this->listeners[$scraped->scrapeRequest->type])) {
            resolve($this->listeners[$scraped->scrapeRequest->type])->handle($scraped);
        }
    }
}
