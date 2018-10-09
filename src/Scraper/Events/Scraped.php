<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Scraped
{
    use Dispatchable, SerializesModels;

    /**
     * @var ScrapeRequest
     */
    public $scrapeRequest;

    /**
     * @var array
     */
    public $data;

    /**
     * @var string
     */
    public $variant;

    /**
     * Create a new event instance.
     *
     * @param ScrapeRequest $scrapeRequest
     * @param array         $data
     * @param string        $variant
     */
    public function __construct(ScrapeRequest $scrapeRequest, array $data, string $variant)
    {
        $this->scrapeRequest = $scrapeRequest;
        $this->data          = $data;
        $this->variant       = $variant;
    }
}
