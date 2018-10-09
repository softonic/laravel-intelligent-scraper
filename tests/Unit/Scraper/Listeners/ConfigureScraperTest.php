<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathFinder;
use Softonic\LaravelIntelligentScraper\Scraper\Events\InvalidConfiguration;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeFailed;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration as ConfigurationModel;
use Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration;
use Tests\TestCase;

class ConfigureScraperTest extends TestCase
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

    public function setUp()
    {
        parent::setUp();

        $this->config      = \Mockery::mock(Configuration::class);
        $this->xpathFinder = \Mockery::mock(XpathFinder::class);
        $this->url         = 'http://test.c/123456';
        $this->type        = 'post';
    }

    /**
     * @test
     */
    public function whenCannotBeCalculatedItShouldThrowAnException()
    {
        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andThrow(ConfigurationException::class, 'Configuration cannot be calculated');

        Log::shouldReceive('error')
            ->with(
                "Error scraping 'http://test.c/123456'",
                ['message' => 'Configuration cannot be calculated']
            );

        $configureScraper = new ConfigureScraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );

        $this->expectsEvents(ScrapeFailed::class);

        $scrapeRequest = new ScrapeRequest($this->url, $this->type);
        $configureScraper->handle(new InvalidConfiguration($scrapeRequest));
    }

    /**
     * @test
     */
    public function whenIsCalculatedItShouldReturnExtractedDataAndStoreTheNewConfig()
    {
        $xpathConfig = collect([
            new ConfigurationModel([
                'name'   => 'title',
                'xpaths' => ['//*[@id="page-title"]'],
                'type'   => 'post',
            ]),
            new ConfigurationModel([
                'name'   => 'version',
                'xpaths' => ['/html/div[2]/p'],
                'type'   => 'post',
            ]),
        ]);
        $scrapedData = [
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data'    => [
                'title'   => ['test'],
                'version' => ['1.0'],
            ],
        ];

        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andReturn($scrapedData);

        $configureScraper = new ConfigureScraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );

        $this->expectsEvents(Scraped::class);
        $configureScraper->handle(new InvalidConfiguration(new ScrapeRequest($this->url, $this->type)));

        $event = collect($this->firedEvents)->filter(function ($event) {
            $class = Scraped::class;

            return $event instanceof $class;
        })->first();
        $this->assertEquals(
            $scrapedData['data'],
            $event->data
        );

        $this->assertDatabaseHas(
            'configurations',
            [
                'name'   => 'title',
                'xpaths' => json_encode(['//*[@id="page-title"]']),
            ]
        );
        $this->assertDatabaseHas(
            'configurations',
            [
                'name'   => 'version',
                'xpaths' => json_encode(['/html/div[2]/p']),
            ]
        );
    }

    /**
     * @test
     */
    public function whenScrappingConnectionFailsItShouldThrowAConnectionException()
    {
        $xpathConfig = collect([
            new ConfigurationModel([
                'name'   => 'title',
                'xpaths' => ['//*[@id="page-title"]'],
                'type'   => 'post',
            ]),
            new ConfigurationModel([
                'name'   => 'version',
                'xpaths' => ['/html/div[2]/p'],
                'type'   => 'post',
            ]),
        ]);
        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrowExceptions([\Mockery::mock(ConnectException::class)]);

        $this->expectException(ConnectException::class);

        $configureScraper = new ConfigureScraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $configureScraper->handle(new InvalidConfiguration(new ScrapeRequest($this->url, $this->type)));
    }

    /**
     * @test
     */
    public function whenTheIdStoreIsNotAvailableItShouldThrowAnUnexpectedValueException()
    {
        $xpathConfig = collect([
            new ConfigurationModel([
                'name'   => 'title',
                'xpaths' => ['//*[@id="page-title"]'],
                'type'   => 'post',
            ]),
            new ConfigurationModel([
                'name'   => 'version',
                'xpaths' => ['/html/div[2]/p'],
                'type'   => 'post',
            ]),
        ]);
        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrow(\UnexpectedValueException::class, 'HTTP Error: 404');

        Log::shouldReceive('debug');
        Log::shouldReceive('error')
            ->with("Error scraping 'http://test.c/123456'", ['message' => 'HTTP Error: 404']);
        $this->expectsEvents(ScrapeFailed::class);

        $configureScraper = new ConfigureScraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $configureScraper->handle(new InvalidConfiguration(new ScrapeRequest($this->url, $this->type)));
    }
}
