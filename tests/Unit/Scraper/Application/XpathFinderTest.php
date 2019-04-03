<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration;
use Tests\TestCase;

class XpathFinderTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        Log::spy();
    }

    /**
     * @test
     */
    public function whenExtractUsingAnInvalidUrlStatusItShouldThrowAnException()
    {
        $config = [
            Configuration::create([
                'name'   => 'title',
                'type'   => 'post',
                'xpaths' => ['//*[@id="title"]'],
            ]),
        ];

        $variantGenerator = \Mockery::mock(VariantGenerator::class);

        $requestException = \Mockery::mock(RequestException::class);
        $requestException->shouldReceive('getResponse->getStatusCode')
            ->once()
            ->andReturn(404);

        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'url'
            )
            ->andThrows($requestException);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Response error from \'url\' with \'404\' http code');

        $xpathFinder = new XpathFinder($client, $variantGenerator);
        $xpathFinder->extract('url', $config);
    }

    /**
     * @test
     */
    public function whenExtractUsingAnUnavailableUrlItShouldThrowAnException()
    {
        $config = [
            Configuration::create([
                'name'   => 'title',
                'type'   => 'post',
                'xpaths' => ['//*[@id="title"]'],
            ]),
        ];

        $variantGenerator = \Mockery::mock(VariantGenerator::class);

        $connectException = \Mockery::mock(ConnectException::class);
        $client           = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'url'
            )
            ->andThrows($connectException);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unavailable url \'url\'');

        $xpathFinder = new XpathFinder($client, $variantGenerator);
        $xpathFinder->extract('url', $config);
    }

    /**
     * @test
     */
    public function whenXpathIsMissingAValueItShouldThrowAnException()
    {
        $config = [
            Configuration::create([
                'name'   => 'title',
                'type'   => 'post',
                'xpaths' => [
                    '//*[@id="title"]',
                    '//*[@id="title2"]',
                ],
            ]),
        ];

        $internalXpathFinder = \Mockery::mock(\Symfony\Component\DomCrawler\Crawler::class);

        $variantGenerator = \Mockery::mock(VariantGenerator::class);
        $client           = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'url'
            )
            ->andReturn($internalXpathFinder);

        $internalXpathFinder->shouldReceive('filterXPath')
            ->once()
            ->with('//*[@id="title"]')
            ->andReturnSelf();
        $internalXpathFinder->shouldReceive('filterXPath')
            ->once()
            ->with('//*[@id="title2"]')
            ->andReturnSelf();
        $internalXpathFinder->shouldReceive('count')
            ->andReturn(0);

        $this->expectException(MissingXpathValueException::class);
        $this->expectExceptionMessage('Xpath \'//*[@id="title"]\', \'//*[@id="title2"]\' for field \'title\' not found in \'url\'.');

        $xpathFinder = new XpathFinder($client, $variantGenerator);
        $xpathFinder->extract('url', $config);
    }

    /**
     * @test
     */
    public function whenXpathsAreFoundItShouldReturnTheFoundValues()
    {
        $config = [
            Configuration::create([
                'name'   => 'title',
                'type'   => 'post',
                'xpaths' => [
                    '//*[@id="title"]',
                    '//*[@id="title2"]',
                ],
            ]),
            Configuration::create([
                'name'   => 'author',
                'type'   => 'post',
                'xpaths' => [
                    '//*[@id="author"]',
                    '//*[@id="author2"]',
                ],
            ]),
        ];

        $internalXpathFinder = \Mockery::mock(\Symfony\Component\DomXpathFinder\XpathFinder::class);
        $titleXpathFinder    = \Mockery::mock(\Symfony\Component\DomXpathFinder\XpathFinder::class);
        $authorXpathFinder   = \Mockery::mock(\Symfony\Component\DomXpathFinder\XpathFinder::class);

        $variantGenerator = \Mockery::mock(VariantGenerator::class);
        $variantGenerator->shouldReceive('addConfig')
            ->twice();
        $variantGenerator->shouldReceive('getId')
            ->andReturn(10);

        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->once()
            ->with(
                'GET',
                'url'
            )
            ->andReturn($internalXpathFinder);

        $internalXpathFinder->shouldReceive('filterXPath')
            ->once()
            ->with('//*[@id="title"]')
            ->andReturnSelf();
        $internalXpathFinder->shouldReceive('filterXPath')
            ->once()
            ->with('//*[@id="title2"]')
            ->andReturn($titleXpathFinder);
        $internalXpathFinder->shouldReceive('filterXPath')
            ->once()
            ->with('//*[@id="author"]')
            ->andReturn($authorXpathFinder);
        $internalXpathFinder->shouldReceive('filterXPath')
            ->never()
            ->with('//*[@id="author2"]');
        $internalXpathFinder->shouldReceive('count')
            ->andReturn(0);
        $titleXpathFinder->shouldReceive('count')
            ->andReturn(1);
        $authorXpathFinder->shouldReceive('count')
            ->andReturn(1);
        $authorXpathFinder->shouldReceive('each')
            ->andReturn(['My author']);
        $titleXpathFinder->shouldReceive('each')
            ->andReturn(['My Title']);

        $xpathFinder   = new XpathFinder($client, $variantGenerator);
        $extractedData = $xpathFinder->extract('url', $config);

        $this->assertEquals(
            [
                'variant' => 10,
                'data'    => [
                    'title'  => ['My Title'],
                    'author' => ['My author'],
                ],
            ],
            $extractedData
        );
    }
}
