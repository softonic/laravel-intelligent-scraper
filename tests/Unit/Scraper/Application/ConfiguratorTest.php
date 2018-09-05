<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;
use Tests\TestCase;

class ConfiguratorTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function whenTryToFindNewXpathButUrlFromDatasetIsNotValidThrowAnExceptionAndRemoveIt()
    {
        $posts        = [
            new ScrapedDataset([
                'url'  => 'https://test.c/123456789012',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
        ];
        $client       = \Mockery::mock(Client::class);
        $xpathBuilder = \Mockery::mock(XpathBuilder::class);

        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $client->shouldReceive('getInternalResponse->getStatus')
            ->once()
            ->andReturn(404);

        $configurator = new Configurator($client, $xpathBuilder);

        try {
            $configurator->configureFromDataset($posts);
        } catch (ConfigurationException $e) {
            $this->assertEquals('Field(s) "title,author" not found.', $e->getMessage());
            $this->assertDatabaseMissing('scraped_datasets', ['url' => 'https://test.c/123456789012']);
        }
    }

    /**
     * @test
     */
    public function whenTryToFindNewXpathButNotFoundItShouldLogIt()
    {
        $posts        = [
            ScrapedDataset::make([
                'url'  => 'https://test.c/123456789012',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
        ];
        $client       = \Mockery::mock(Client::class);
        $xpathBuilder = \Mockery::mock(XpathBuilder::class);

        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $client->shouldReceive('getInternalResponse->getStatus')
            ->once()
            ->andReturn(200);

        $rootElement = new \DOMElement('test');
        $client->shouldReceive('getNode')
            ->with(0)
            ->andReturn($rootElement);
        $client->shouldReceive('getUri')
            ->andReturn('https://test.c/123456789012');

        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title')
            ->andReturn('//*[|id="title"]');
        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author')
            ->andThrow(\UnexpectedValueException::class);

        Log::shouldReceive('warning')
            ->with("Field 'author' with value 'My author' not found for 'https://test.c/123456789012'.");

        $configurator = new Configurator($client, $xpathBuilder);

        try {
            $configurator->configureFromDataset($posts);
        } catch (ConfigurationException $e) {
            $this->assertEquals('Field(s) "author" not found.', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function whenTryToFindXpathInMultiplepostsAndNotFoundInAnyItShouldThrowAnExceptionAndRemoveThem()
    {
        $posts        = [
            ScrapedDataset::make([
                'url'  => 'https://test.c/123456789012',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
            ScrapedDataset::make([
                'url'  => 'https://test.c/123456789022',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
        ];
        $client       = \Mockery::mock(Client::class);
        $xpathBuilder = \Mockery::mock(XpathBuilder::class);

        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789022'
            )
            ->andReturnSelf();
        $client->shouldReceive('getInternalResponse->getStatus')
            ->andReturn(200);
        $client->shouldReceive('getUri')
            ->andReturn('https://test.c/123456789012');

        $rootElement = new \DOMElement('test');
        $client->shouldReceive('getNode')
            ->with(0)
            ->andReturn($rootElement);

        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title')
            ->andThrow(\UnexpectedValueException::class);
        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author')
            ->andThrow(\UnexpectedValueException::class);

        Log::shouldReceive('warning')
            ->with("Field 'title' with value 'My Title' not found for 'https://test.c/123456789012'.");

        Log::shouldReceive('warning')
            ->with("Field 'author' with value 'My author' not found for 'https://test.c/123456789012'.");

        $configurator = new Configurator($client, $xpathBuilder);

        try {
            $configurator->configureFromDataset($posts);
        } catch (ConfigurationException $e) {
            $this->assertEquals('Field(s) "title,author" not found.', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function whenDiscoverDifferentXpathItShouldGetAllOfThem()
    {
        $posts        = [
            ScrapedDataset::make([
                'url'  => 'https://test.c/123456789012',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
            ScrapedDataset::make([
                'url'  => 'https://test.c/123456789022',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title',
                    'author' => 'My author',
                ],
            ]),
            ScrapedDataset::make([
                'url'  => 'https://test.c/123456789033',
                'type' => 'post',
                'data' => [
                    'title'  => 'My Title2',
                    'author' => 'My author2',
                ],
            ]),
        ];
        $client       = \Mockery::mock(Client::class);
        $xpathBuilder = \Mockery::mock(XpathBuilder::class);

        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789012'
            )
            ->andReturnSelf();
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789022'
            )
            ->andReturnSelf();
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'https://test.c/123456789033'
            )
            ->andReturnSelf();
        $client->shouldReceive('getInternalResponse->getStatus')
            ->andReturn(200);

        $rootElement = new \DOMElement('test');
        $client->shouldReceive('getNode')
            ->with(0)
            ->andReturn($rootElement);

        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title')
            ->andReturn('//*[|id="title"]');
        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author')
            ->andReturn('//*[|id="author"]');
        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My Title2')
            ->andReturn('//*[|id="title2"]');
        $xpathBuilder->shouldReceive('find')
            ->with($rootElement, 'My author2')
            ->andReturn('//*[|id="author2"]');

        $configurator = new Configurator($client, $xpathBuilder);

        $configurations = $configurator->configureFromDataset($posts);

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
    }
}
