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
     * @var array
     */
    public $context;

    /**
     * Create a new event instance.
     *
     * @param string $url
     * @param string $type
     * @param array  $context
     */
    public function __construct(string $url, string $type, array $context = [])
    {
        $this->url     = $url;
        $this->type    = $type;
        $this->context = $context;
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
        return ["request_type:{$this->type}"];
    }
}
