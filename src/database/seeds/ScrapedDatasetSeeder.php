<?php

use Illuminate\Database\Seeder;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

class ScrapedDatasetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     */
    public function run()
    {
        factory(ScrapedDataset::class, 2)->create();
    }

    public function createScrapedDatasets(int $amount): \Illuminate\Support\Collection
    {
        return factory(ScrapedDataset::class, $amount)->create();
    }
}
