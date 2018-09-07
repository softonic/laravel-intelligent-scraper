<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScrapeRequest
{
    use Dispatchable, SerializesModels;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $type;

    /**
     * Create a new event instance.
     *
     * @param string $url
     * @param string $type
     */
    public function __construct(string $url, string $type)
    {
        $this->url  = $url;
        $this->type = $type;
    }
}
