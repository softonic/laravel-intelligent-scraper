<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ConfigurationScraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
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

    /**
     * @var VariantGenerator
     */
    private $variantGenerator;

    /**
     * @var \Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration
     */
    private $configuration;

    public function __construct(
        Client $client,
        XpathBuilder $xpathBuilder,
        \Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration $configuration,
        VariantGenerator $variantGenerator
    ) {
        $this->client           = $client;
        $this->xpathBuilder     = $xpathBuilder;
        $this->variantGenerator = $variantGenerator;
        $this->configuration    = $configuration;
    }

    /**
     * @param ScrapedDataset[] $scrapedDataset
     *
     * @return \Illuminate\Support\Collection
     */
    public function configureFromDataset($scrapedDataset): Collection
    {
        $type                 = $scrapedDataset[0]['type'];
        $currentConfiguration = $this->configuration->findByType($type);

        $result        = [];
        $totalDatasets = $scrapedDataset;
        foreach ($scrapedDataset as $key => $scrapedData) {
            Log::info("Finding config {$key}/{$totalDatasets}");
            if ($crawler = $this->getCrawler($scrapedData)) {
                $result[] = $this->findConfigByScrapedData($scrapedData, $crawler, $currentConfiguration);
            }
        }

        $finalConfig = $this->mergeConfiguration($result, $type);

        $this->checkConfiguration($scrapedDataset[0]['data'], $finalConfig);

        return $finalConfig;
    }

    private function getCrawler($scrapedData)
    {
        Log::info("Request {$scrapedData['url']}");
        $crawler = $this->client->request('GET', $scrapedData['url']);

        $httpCode = $this->client->getInternalResponse()->getStatus();
        if ($httpCode !== 200) {
            Log::notice(
                "Response status ({$httpCode}) invalid, so proceeding to delete the scraped dadta.",
                compact('scrapedData')
            );
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
     * @param ScrapedDataset  $scrapedData
     * @param Crawler         $crawler
     * @param Configuration[] $currentConfiguration
     *
     * @return array
     */
    private function findConfigByScrapedData($scrapedData, $crawler, $currentConfiguration)
    {
        $result = [];

        foreach ($scrapedData['data'] as $field => $value) {
            try {
                Log::info("Searching xpath for field {$field}");
                $result[$field] = $this->getOldXpath($currentConfiguration, $field, $crawler);
                if (!$result[$field]) {
                    Log::debug('Trying to find a new xpath.');
                    $result[$field] = $this->xpathBuilder->find(
                        $crawler->getNode(0),
                        $value
                    );
                }
                $this->variantGenerator->addConfig($field, $result[$field]);
                Log::info('Added found xpath to the config');
            } catch (\UnexpectedValueException $e) {
                $this->variantGenerator->fieldNotFound();
                $value = is_array($value) ? json_encode($value) : $value;
                Log::notice("Field '{$field}' with value '{$value}' not found for '{$crawler->getUri()}'.");
            }
        }

        event(new ConfigurationScraped(
            new ScrapeRequest(
                $scrapedData['url'],
                $scrapedData['type']
            ),
            $scrapedData['data'],
            $this->variantGenerator->getId($scrapedData['type'])
        ));

        return $result;
    }

    private function getOldXpath($currentConfiguration, $field, $crawler)
    {
        Log::debug('Checking old Xpaths');
        $config = $currentConfiguration->firstWhere('name', $field);
        foreach ($config['xpaths'] ?? [] as $xpath) {
            Log::debug("Checking xpath {$xpath}");
            $isFound = $crawler->filterXPath($xpath)->count();
            if ($isFound) {
                return $xpath;
            }
        }

        Log::debug('Old xpath not found');

        return false;
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
