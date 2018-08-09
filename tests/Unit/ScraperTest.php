<?php

namespace Softonic\LaravelIntelligentScraper;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathFinder;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration as ConfigurationModel;
use Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration;
use Tests\TestCase;

class ScraperTest extends TestCase
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
    public function whenConfigurationDoesNotExistAndCannotBeCalculatedItShouldThrowAnException()
    {
        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn(collect());

        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andThrow(ConfigurationException::class, 'Configuration cannot be calculated');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Configuration cannot be calculated');

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scraper->getData($this->url, $this->type);
    }

    /**
     * @test
     */
    public function whenConfigurationDoesNotExistAndIsCalculatedItShouldReturnExtractedData()
    {
        $xpathConfig = [
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ];
        $scrapedData = [
            'title'   => ['test'],
            'version' => ['1.0'],
        ];

        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn(collect());

        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andReturn($scrapedData);

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );

        $this->assertEquals(
            $scrapedData,
            $scraper->getData($this->url, $this->type)
        );
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

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scraper->getData($this->url, $this->type);
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

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scraper->getData($this->url, $this->type);
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

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $this->assertEquals(
            $scrapedData,
            $scraper->getData($this->url, $this->type)
        );
    }

    /**
     * @test
     */
    public function whenTheScraperConfigIsDeprecatedAndCannotBeCalculatedItShouldThrowAnException()
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

        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andThrow(ConfigurationException::class, 'Configuration cannot be calculated');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Configuration cannot be calculated');

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scraper->getData($this->url, $this->type);
    }

    /**
     * @test
     */
    public function whenTheScraperConfigIsDeprecatedAndIsCalculatedButStillFailingItShouldThrowAnException()
    {
        $xpathConfig           = collect([
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ]);
        $xpathConfigCalculated = [
            'title'   => '//*[@id="title"]',
            'version' => '/html/div[1]/p',
        ];

        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrow(MissingXpathValueException::class, 'XPath configuration is not valid.');

        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfigCalculated);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfigCalculated)
            ->andThrow(MissingXpathValueException::class, 'XPath configuration is not valid.');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("After calculate configuration, 'http://test.c/123456' for type 'post' could not be scraped.");

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $scraper->getData($this->url, $this->type);
    }

    /**
     * @test
     */
    public function whenSuccessToScrapeAfterConfigRecalculationItShouldReturnTheData()
    {
        $scrapedData           = [
            'title'   => 'test',
            'version' => '1.0',
        ];
        $xpathConfig           = collect([
            'title'   => '//*[@id="page-title"]',
            'version' => '/html/div[2]/p',
        ]);
        $xpathConfigCalculated = collect([
            ConfigurationModel::make([
                'name'   => 'title',
                'type'   => $this->type,
                'xpaths' => ['//*[@id="title"]'],

            ]),
            ConfigurationModel::make([
                'name'   => 'version',
                'type'   => $this->type,
                'xpaths' => ['/html/div[1]/p'],
            ]),
        ]);

        $this->config->shouldReceive('findByType')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfig);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfig)
            ->andThrow(MissingXpathValueException::class, 'XPath configuration is not valid.');

        $this->config->shouldReceive('calculate')
            ->once()
            ->with($this->type)
            ->andReturn($xpathConfigCalculated);

        $this->xpathFinder->shouldReceive('extract')
            ->once()
            ->with('http://test.c/123456', $xpathConfigCalculated)
            ->andReturn($scrapedData);

        $scraper = new Scraper(
            $this->config,
            $this->xpathFinder,
            Log::getFacadeRoot()
        );
        $this->assertEquals(
            $scrapedData,
            $scraper->getData($this->url, $this->type)
        );

        $this->assertNotEmpty(ConfigurationModel::find('title'));
        $this->assertNotEmpty(ConfigurationModel::find('version'));
    }
}
