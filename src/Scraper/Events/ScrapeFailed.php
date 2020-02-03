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
            "failed_type:{$this->scrapeRequest->type}",
        ];
    }
}
