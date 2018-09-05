<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathFinder;
use Softonic\LaravelIntelligentScraper\Scraper\Events\InvalidConfiguration;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;
use Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration;
use Tests\TestCase;

class ScrapeTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var MockInterface | Configuration
     */
    private $config;

    /**
     * @var MockInterface | XpathFinder
     */
    private $xpathFinder;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $type;

    /**
     * @var ScrapeRequest
     */
    private $scrapeRequest;

    public function setUp()
    {
        parent::setUp();

        $this->config        = \Mockery::mock(Configuration::class);
        $this->xpathFinder   = \Mockery::mock(XpathFinder::class);
        $this->url           = 'http://test.c/123456';
        $this->type          = 'post';
        $this->scrapeRequest = new ScrapeRequest($this->url, $this->type);
    }

    /**
     * @test
     */
    public function whenConfigurationDoesNotExistItShouldThrowAnEvent()
    {
        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn(collect());

        $this->expectsEvents(InvalidConfiguration::class);

        $scrape = new Scrape(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );

        $scrape->handle($this->scrapeRequest);
    }

    /**
     * @test
     */
    public function whenScrappingConnectionFailsItShouldThrowAConnectionException()
    {
        $xpathConfig = collect([
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ]);
        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrowExceptions([\Mockery::mock(ConnectException::class)]);

        $this->expectException(ConnectException::class);

        $scrape = new Scrape(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scrape->handle($this->scrapeRequest);
    }

    /**
     * @test
     */
    public function whenTheIdStoreIsNotAvailableItShouldThrowAnUnexpectedValueException()
    {
        $xpathConfig = collect([
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ]);
        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrow(\UnexpectedValueException::class, 'HTTP Error: 404');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('HTTP Error: 404');

        $scrape = new Scrape(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scrape->handle($this->scrapeRequest);
    }

    /**
     * @test
     */
    public function whenTheDataExtractionWorksItShouldReturnsTheScrapedData()
    {
        $scrapedData = [
            'title'   => ['test'],
            'version' => ['1.0'],
        ];
        $xpathConfig = collect([
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ]);
        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andReturn($scrapedData);

        $scrape = new Scrape(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );

        $this->expectsEvents(Scraped::class);
        $scrape->handle($this->scrapeRequest);

        $event = collect($this->firedEvents)->filter(function ($event) {
            $class = Scraped::class;

            return $event instanceof $class;
        })->first();
        $this->assertEquals(
            $scrapedData,
            $event->data
        );
    }

    /**
     * @test
     */
    public function whenTheScraperConfigIsInvalidItShouldTriggerAnEvent()
    {
        $xpathConfig = collect([
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ]);
        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrow(MissingXpathValueException::class, 'XPath configuration is not valid.');

        $this->expectsEvents(InvalidConfiguration::class);

        $scrape = new Scrape(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scrape->handle($this->scrapeRequest);
    }
}
