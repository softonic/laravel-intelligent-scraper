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

    /**
     * Get the tags that should be assigned to the job.
     *
     * Only if you are using Horizon
     *
     * @see https://laravel.com/docs/5.8/horizon#tags
     *
     * @return array
     */
    public function tags()
    {
        $type    = $this->scrapeRequest->type;
        $variant = $this->variant;

        return [
            "scraped_type:$type",
            "scraped_variant:$variant",
        ];
    }
}
