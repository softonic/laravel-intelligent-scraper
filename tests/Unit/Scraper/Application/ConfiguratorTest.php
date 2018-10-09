<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Mockery\Mock;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;
use Tests\TestCase;

class ConfiguratorTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var Mock | Client
     */
    private $client;

    /**
     * @var Mock | XpathBuilder
     */
    private $xpathBuilder;

    /**
     * @var Mock | VariantGenerator
     */
    private $variantGenerator;

    /**
     * @var Mock | Configurator
     */
    private $configurator;

    public function setUp()
    {
        parent::setUp();

        $this->client           = \Mockery::mock(Client::class);
        $this->xpathBuilder     = \Mockery::mock(XpathBuilder::class);
        $this->variantGenerator = \Mockery::mock(VariantGenerator::class);

        $this->configurator = new Configurator($this->client, $this->xpathBuilder, $this->variantGenerator);
    }

    /**
     * @test
     */
    public function whenTryToFindNewXpathButUrlFromDatasetIsNotValidThrowAnExceptionAndRemoveIt()
    {
        $posts = [
            new ScrapedDataset([
                'url'  => 'https://test.c/123456789012',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
        ];

        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('getInternalResponse->getStatus')
            ->once()
            ->andReturn(404);

        try {
            $this->configurator->configureFromDataset($posts);
        } catch (ConfigurationException $e) {
            $this->assertEquals('Field(s) "title,author" not found.', $e->getMessage());
            $this->assertDatabaseMissing('scraped_datasets', ['url' => 'https://test.c/123456789012']);
        }
    }

    /**
     * @test
     */
    public function whenTryToFindNewXpathButNotFoundItShouldLogItAndResetVariant()
    {
        $posts = [
            ScrapedDataset::create([
                'url'     => 'https://test.c/123456789012',
                'type'    => 'post',
                'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
                'data'    => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
        ];

        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('getInternalResponse->getStatus')
            ->once()
            ->andReturn(200);

        $rootElement = new \DOMElement('test');
        $this->client->shouldReceive('getNode')
            ->with(0)
            ->andReturn($rootElement);
        $this->client->shouldReceive('getUri')
            ->andReturn('https://test.c/123456789012');

        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title')
            ->andReturn('//*[|id="title"]');
        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author')
            ->andThrow(\UnexpectedValueException::class);

        $this->variantGenerator->shouldReceive('addConfig')
            ->withAnyArgs();
        $this->variantGenerator->shouldReceive('fieldNotFound')
            ->once();
        $this->variantGenerator->shouldReceive('getId')
            ->andReturnNull();

        Log::shouldReceive('warning')
            ->with("Field 'author' with value 'My author' not found for 'https://test.c/123456789012'.");

        try {
            $this->configurator->configureFromDataset($posts);
        } catch (ConfigurationException $e) {
            $this->assertEquals('Field(s) "author" not found.', $e->getMessage());
        }

        $this->assertNull($posts[0]['variant']);
    }

    /**
     * @test
     */
    public function whenTryToFindXpathInMultiplepostsAndNotFoundInAnyItShouldThrowAnExceptionAndLogItAndResetVariant()
    {
        $posts = [
            ScrapedDataset::make([
                'url'     => 'https://test.c/123456789012',
                'type'    => 'post',
                'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
                'data'    => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
            ScrapedDataset::make([
                'url'     => 'https://test.c/123456789022',
                'type'    => 'post',
                'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
                'data'    => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
        ];

        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789022'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('getInternalResponse->getStatus')
            ->andReturn(200);
        $this->client->shouldReceive('getUri')
            ->andReturn('https://test.c/123456789012');

        $rootElement = new \DOMElement('test');
        $this->client->shouldReceive('getNode')
            ->with(0)
            ->andReturn($rootElement);

        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title')
            ->andThrow(\UnexpectedValueException::class);
        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author')
            ->andThrow(\UnexpectedValueException::class);

        $this->variantGenerator->shouldReceive('addConfig')
            ->never();
        $this->variantGenerator->shouldReceive('fieldNotFound')
            ->times(4);
        $this->variantGenerator->shouldReceive('getId')
            ->andReturnNull();

        Log::shouldReceive('warning')
            ->with("Field 'title' with value 'My Title' not found for 'https://test.c/123456789012'.");

        Log::shouldReceive('warning')
            ->with("Field 'author' with value 'My author' not found for 'https://test.c/123456789012'.");

        try {
            $this->configurator->configureFromDataset($posts);
        } catch (ConfigurationException $e) {
            $this->assertEquals('Field(s) "title,author" not found.', $e->getMessage());
        }

        $this->assertNull($posts[0]['variant']);
        $this->assertNull($posts[1]['variant']);
    }

    /**
     * @test
     */
    public function whenDiscoverDifferentXpathItShouldGetAllOfThemAndUpdateTheVariants()
    {
        $posts = [
            ScrapedDataset::make([
                'url'     => 'https://test.c/123456789012',
                'type'    => 'post',
                'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
                'data'    => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
            ScrapedDataset::make([
                'url'     => 'https://test.c/123456789022',
                'type'    => 'post',
                'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
                'data'    => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
            ScrapedDataset::make([
                'url'     => 'https://test.c/123456789033',
                'type'    => 'post',
                'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
                'data'    => [
                    'title'  => 'My Title2',
                    'author' => 'My author2',
                ],
            ]),
        ];

        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789022'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789033'
            )
            ->andReturnSelf();
        $this->client->shouldReceive('getInternalResponse->getStatus')
            ->andReturn(200);

        $rootElement = new \DOMElement('test');
        $this->client->shouldReceive('getNode')
            ->with(0)
            ->andReturn($rootElement);

        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title')
            ->andReturn('//*[|id="title"]');
        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author')
            ->andReturn('//*[|id="author"]');
        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title2')
            ->andReturn('//*[|id="title2"]');
        $this->xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author2')
            ->andReturn('//*[|id="author2"]');

        $this->variantGenerator->shouldReceive('addConfig')
            ->withAnyArgs();
        $this->variantGenerator->shouldReceive('fieldNotFound')
            ->never();
        $this->variantGenerator->shouldReceive('getId')
            ->andReturn(10, 20, 30);

        $configurations = $this->configurator->configureFromDataset($posts);

        $this->assertInstanceOf(Configuration::class, $configurations[0]);
        $this->assertEquals('title', $configurations[0]['name']);
        $this->assertEquals('post', $configurations[0]['type']);
        $this->assertEquals(
            [
                '//*[|id="title"]',
                '//*[|id="title2"]',
            ],
            array_values($configurations[0]['xpaths'])
        );

        $this->assertInstanceOf(Configuration::class, $configurations[1]);
        $this->assertEquals('author', $configurations[1]['name']);
        $this->assertEquals('post', $configurations[1]['type']);
        $this->assertEquals(
            [
                '//*[|id="author"]',
                '//*[|id="author2"]',
            ],
            array_values($configurations[1]['xpaths'])
        );

        $this->assertEquals($posts[0]['variant'], 10);
        $this->assertEquals($posts[1]['variant'], 20);
        $this->assertEquals($posts[2]['variant'], 30);
    }
}
