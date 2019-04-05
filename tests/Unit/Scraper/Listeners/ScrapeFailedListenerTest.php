<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeFailed;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Tests\TestCase;

class ScrapeFailedListenerTest extends TestCase
{
    /**
     * @test
     */
    public function whenReceiveAnUnknownScrapeFailedTypeItShouldDoNothing()
    {
        $listener = \Mockery::mock(ScrapeFailedListener::class);
        \App::instance(get_class($listener), $listener);

        $scrapeFailedListener = new ScrapeFailedListener([
            'known_type' => get_class($listener),
        ]);

        $scrapeFailedEvent = new ScrapeFailed(
            new ScrapeRequest(
                'http://uri',
                'unknown_type'
            ),
            [],
            1
        );

        $listener->shouldNotReceive('handle');

        $scrapeFailedListener->handle($scrapeFailedEvent);
    }

    /**
     * @test
     */
    public function whenReceiveAKnownScrapeFailedTypeItShouldHandleTheEventWithTheSpecificDependency()
    {
        $listener = \Mockery::mock(ScrapeFailedListener::class);
        \App::instance(get_class($listener), $listener);

        $scrapeFailedListener = new ScrapeFailedListener([
            'known_type' => get_class($listener),
        ]);

        $scrapeFailedEvent = new ScrapeFailed(
            new ScrapeRequest(
                'http://uri',
                'known_type'
            ),
            [],
            1
        );

        $listener->shouldReceive('handle')
            ->once()
            ->with($scrapeFailedEvent);

        $scrapeFailedListener->handle($scrapeFailedEvent);
    }
}
