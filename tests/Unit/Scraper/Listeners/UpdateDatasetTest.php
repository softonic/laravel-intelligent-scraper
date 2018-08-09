<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

class UpdateDatasetTest extends \Tests\TestCase
{
    use DatabaseMigrations;

    /**
     * @var UpdateDataset
     */
    private $updateDataset;

    public function setUp()
    {
        parent::setUp();

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

        $this->updateDataset->handle(new Scraped($dataset->url, 'post', $data));

        $this->assertEquals($data, ScrapedDataset::where('url', $dataset->url)->first()->toArray()['data']);
        $this->assertEquals(2, ScrapedDataset::all()->count());
    }

    /**
     * @test
     */
    public function whenDatasetDoesNotExistAndTheDatasetsLimitHasNotBeenReachedItShouldBeSaved()
    {
        $seeder = new \ScrapedDatasetSeeder();
        $seeder->createScrapedDatasets(2);

        $url  = 'https//store-url.com/id';
        $data = [
            'title'    => ['My first post'],
            'author'   => ['Jhon Doe'],
            'category' => ['Entertainment'],
        ];

        $this->updateDataset->handle(new Scraped($url, 'post', $data));

        $this->assertEquals($data, ScrapedDataset::where('url', $url)->first()->toArray()['data']);
        $this->assertEquals(3, ScrapedDataset::all()->count());
    }

    /**
     * @test
     */
    public function whenDatasetDoesNotExistAndTheDatasetsLimitHasReachedItShouldReplaceTheOldest()
    {
        $seeder = new \ScrapedDatasetSeeder();
        $seeder->createScrapedDatasets(UpdateDataset::DATASET_AMOUNT_LIMIT);

        $url  = 'https//store-url.com/id';
        $type = 'post';
        $data = [
            'title'  => ['My first post'],
            'author' => ['Jhon Doe'],
        ];

        $this->updateDataset->handle(new Scraped($url, $type, $data));

        $this->assertEquals($data, ScrapedDataset::where('url', $url)->first()->toArray()['data']);
        $this->assertEquals(UpdateDataset::DATASET_AMOUNT_LIMIT, ScrapedDataset::withType($type)->count());
    }
}
