<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Scraped
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
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param string $url
     * @param string $type
     * @param array  $data
     */
    public function __construct(string $url, string $type, array $data)
    {
        $this->url  = $url;
        $this->type = $type;
        $this->data = $data;
    }
}
