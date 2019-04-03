<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;

class ScrapedListener
{
    private $listeners;

    public function __construct($listeners)
    {
        $this->listeners = $listeners;
    }

    public function handle(Scraped $scraped)
    {
        if (!isset($this->listeners[$scraped->scrapeRequest->type])) {
            throw new \InvalidArgumentException();
        }

        resolve($this->listeners[$scraped->scrapeRequest->type])->handle($scraped);
    }
}
