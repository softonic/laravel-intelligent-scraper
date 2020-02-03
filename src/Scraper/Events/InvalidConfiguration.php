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
        return [
            "reconfigure_type:{$this->scrapeRequest->type}",
        ];
    }
}
