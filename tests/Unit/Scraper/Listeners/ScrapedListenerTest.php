<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Tests\TestCase;

class ScrapedListenerTest extends TestCase
{
    /**
     * @test
     */
    public function whenReceiveAnUnknownScrapedTypeItShouldDoNothing()
    {
        $listener = \Mockery::mock(ScrapedListener::class);
        \App::instance(get_class($listener), $listener);

        $scrapedListener = new ScrapedListener([
            'known_type' => get_class($listener),
        ]);

        $scrapedEvent = new Scraped(
            new ScrapeRequest(
                'http://uri',
                'unknown_type'
            ),
            [],
            1
        );

        $listener->shouldNotReceive('handle');

        $scrapedListener->handle($scrapedEvent);
    }

    /**
     * @test
     */
    public function whenReceiveAKnownScrapedTypeItShouldHandleTheEventWithTheSpecificDependency()
    {
        $listener = \Mockery::mock(ScrapedListener::class);
        \App::instance(get_class($listener), $listener);

        $scrapedListener = new ScrapedListener([
            'known_type' => get_class($listener),
        ]);

        $scrapedEvent = new Scraped(
            new ScrapeRequest(
                'http://uri',
                'known_type'
            ),
            [],
            1
        );

        $listener->shouldReceive('handle')
            ->once()
            ->with($scrapedEvent);

        $scrapedListener->handle($scrapedEvent);
    }
}
