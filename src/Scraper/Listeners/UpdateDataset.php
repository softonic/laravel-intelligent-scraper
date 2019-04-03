<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

class UpdateDataset implements ShouldQueue
{
    const DATASET_AMOUNT_LIMIT = 100;

    public function handle(Scraped $event)
    {
        $datasets = ScrapedDataset::where('url', $event->scrapeRequest->url)->get();

        if ($datasets->isEmpty()) {
            $this->addDataset($event);
        } else {
            $this->updateDataset($datasets->first(), $event);
        }
    }

    private function addDataset(Scraped $event)
    {
        Log::info('Adding new information to dataset', ['request' => $event->scrapeRequest]);
        ScrapedDataset::create(
            [
                'url'     => $event->scrapeRequest->url,
                'type'    => $event->scrapeRequest->type,
                'variant' => $event->variant,
                'data'    => $event->data,
            ]
        );

        $this->deleteExceededDataset($event);
    }

    private function updateDataset(ScrapedDataset $dataset, Scraped $event)
    {
        Log::info('Updating new information to dataset', ['request' => $event->scrapeRequest]);
        $dataset->data = $event->data;

        $dataset->save();
    }

    private function deleteExceededDataset($event): void
    {
        $scraperDatasets = ScrapedDataset::withType($event->scrapeRequest->type)
            ->withVariant($event->variant);

        $datasetAmountAvailable = $scraperDatasets->count();

        if (self::DATASET_AMOUNT_LIMIT <= $datasetAmountAvailable) {
            $datasetToBeDeleted = $datasetAmountAvailable - self::DATASET_AMOUNT_LIMIT;
            Log::debug('Deleting old dataset information', [
                'limit'       => self::DATASET_AMOUNT_LIMIT,
                'current'     => $datasetAmountAvailable,
                'toBeDeleted' => $datasetToBeDeleted,
            ]);

            $scraperDatasets->orderBy('updated_at', 'desc')
                ->take($datasetToBeDeleted)
                ->delete();
        }
    }
}
