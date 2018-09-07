<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScrapeFailed
{
    use Dispatchable, SerializesModels;

    /**
     * @var ScrapeRequest
     */
    public $scrapeRequest;

    /**
     * Create a new event instance.
     *
     * @param ScrapeRequest $scrapeRequest
     */
    public function __construct(ScrapeRequest $scrapeRequest)
    {
        $this->scrapeRequest = $scrapeRequest;
    }
}
