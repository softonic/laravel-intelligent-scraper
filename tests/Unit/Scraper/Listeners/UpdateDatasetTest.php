<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

class UpdateDatasetTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /**
     * @var UpdateDataset
     */
    private $updateDataset;

    public function setUp(): void
    {
        parent::setUp();

        Log::spy();

        $this->updateDataset = new UpdateDataset();
    }

    /**
     * @test
     */
    public function whenDatasetExistsItShouldBeUpdated()
    {
        $seeder  = new \ScrapedDatasetSeeder();
        $dataset = $seeder->createScrapedDatasets(2)->first();

        $data = [
            'title'    => ['My first post'],
            'author'   => ['Jhon Doe'],
            'category' => ['Entertainment'],
        ];

        $this->updateDataset->handle(
            new Scraped(
                new ScrapeRequest($dataset->url, 'post'),
                $data,
                'b265521fc089ac61b794bfa3a5ce8a657f6833ce'
            )
        );

        $this->assertEquals($data, ScrapedDataset::where('url', $dataset->url)->first()->toArray()['data']);
        $this->assertEquals(2, ScrapedDataset::all()->count());
    }

    /**
     * @test
     */
    public function whenDatasetDoesNotExistAndTheDatasetsLimitHasNotBeenReachedItShouldBeSaved()
    {
        factory(ScrapedDataset::class, UpdateDataset::DATASET_AMOUNT_LIMIT - 1)->create([
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
        ]);
        factory(ScrapedDataset::class)->create([
            'variant' => 'f45a8de53eaeea347a83ebaafaf29f16a1dd97e0',
        ]);

        $url  = 'https//store-url.com/id';
        $data = [
            'title'    => ['My first post'],
            'author'   => ['Jhon Doe'],
            'category' => ['Entertainment'],
        ];

        $this->updateDataset->handle(
            new Scraped(
                new ScrapeRequest($url, 'post'),
                $data,
                'b265521fc089ac61b794bfa3a5ce8a657f6833ce'
            )
        );

        $this->assertEquals($data, ScrapedDataset::where('url', $url)->first()->toArray()['data']);
        $this->assertEquals(101, ScrapedDataset::count());
    }

    /**
     * @test
     */
    public function whenDatasetDoesNotExistAndTheDatasetsLimitHasReachedItShouldDeleteTheExcess()
    {
        factory(ScrapedDataset::class, UpdateDataset::DATASET_AMOUNT_LIMIT + 10)->create([
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
        ]);

        $url  = 'https//store-url.com/id';
        $type = 'post';
        $data = [
            'title'  => ['My first post'],
            'author' => ['Jhon Doe'],
        ];

        $this->updateDataset->handle(
            new Scraped(
                new ScrapeRequest($url, $type),
                $data,
                'b265521fc089ac61b794bfa3a5ce8a657f6833ce'
            )
        );

        $this->assertEquals($data, ScrapedDataset::where('url', $url)->first()->toArray()['data']);
        $this->assertEquals(UpdateDataset::DATASET_AMOUNT_LIMIT, ScrapedDataset::withType($type)->count());
    }
}
