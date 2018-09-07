<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;
use Symfony\Component\DomCrawler\Crawler;

class Configurator
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var XpathBuilder
     */
    private $xpathBuilder;

    public function __construct(Client $client, XpathBuilder $xpathBuilder)
    {
        $this->client       = $client;
        $this->xpathBuilder = $xpathBuilder;
    }

    /**
     * @param ScrapedDataset[] $scrapedDataset
     *
     * @return \Illuminate\Support\Collection
     */
    public function configureFromDataset($scrapedDataset): Collection
    {
        $result = [];
        foreach ($scrapedDataset as $scrapedData) {
            if ($crawler = $this->getCrawler($scrapedData)) {
                $result[] = $this->findConfigByScrapedData($scrapedData, $crawler);
            }
        }

        $finalConfig = $this->mergeConfiguration($result, $scrapedDataset[0]['type']);

        $this->checkConfiguration($scrapedDataset[0]['data'], $finalConfig);

        return $finalConfig;
    }

    private function getCrawler($scrapedData)
    {
        $crawler = $this->client->request('GET', $scrapedData['url']);

        $httpCode = $this->client->getInternalResponse()->getStatus();
        if ($httpCode !== 200) {
            $scrapedData->delete();

            return null;
        }

        return $crawler;
    }

    /**
     * Tries to find a new config.
     *
     * If the data is not valid anymore, it is deleted from dataset.
     *
     * @param ScrapedDataset $scrapedData
     * @param Crawler        $crawler
     *
     * @return array
     */
    private function findConfigByScrapedData($scrapedData, $crawler)
    {
        $result = [];
        foreach ($scrapedData['data'] as $field => $value) {
            try {
                $result[$field] = $this->xpathBuilder->find(
                    $crawler->getNode(0),
                    $value
                );
            } catch (\UnexpectedValueException $e) {
                $value = is_array($value) ? json_encode($value) : $value;
                Log::warning("Field '{$field}' with value '{$value}' not found for '{$crawler->getUri()}'.");
            }
        }

        return $result;
    }

    /**
     * Merge configuration.
     *
     * Assign to a field all the possible Xpath.
     *
     * @param array  $result
     * @param string $type
     *
     * @return \Illuminate\Support\Collection
     */
    private function mergeConfiguration($result, string $type): Collection
    {
        $fieldConfig = [];
        foreach ($result as $configs) {
            foreach ($configs as $field => $configurations) {
                $fieldConfig[$field][] = $configurations;
            }
        }

        $finalConfig = collect();
        foreach ($fieldConfig as $field => $xpaths) {
            $finalConfig[] = Configuration::firstOrNew(
                ['name' => $field],
                [
                    'type'   => $type,
                    'xpaths' => array_unique($xpaths),
                ]
            );
        }

        return $finalConfig;
    }

    private function checkConfiguration($data, Collection $finalConfig)
    {
        if (count($finalConfig) != count($data)) {
            $fieldsFound    = $finalConfig->pluck('name')->toArray();
            $fieldsExpected = array_keys($data);

            $fieldsMissing = implode(',', array_diff($fieldsExpected, $fieldsFound));
            throw new ConfigurationException("Field(s) \"{$fieldsMissing}\" not found.", 0);
        }
    }
}
