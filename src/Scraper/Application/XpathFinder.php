<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client as GoutteClient;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;

class XpathFinder
{
    /**
     * @var GoutteClient
     */
    private $client;

    /**
     * @var VariantGenerator
     */
    private $variantGenerator;

    public function __construct(GoutteClient $client, VariantGenerator $variantGenerator)
    {
        $this->client           = $client;
        $this->variantGenerator = $variantGenerator;
    }

    public function extract(string $url, $configs): array
    {
        Log::info("Requesting $url");
        $crawler  = $this->client->request('GET', $url);
        $httpCode = $this->client->getInternalResponse()->getStatus();
        if ($httpCode !== 200) {
            Log::info('Invalid response http status', ['status' => $httpCode]);
            throw new \UnexpectedValueException("Response error from '{$url}' with '{$httpCode}' http code");
        }

        Log::info('Response Received. Starting crawler.');
        $result = [];
        foreach ($configs as $config) {
            Log::info("Searching field {$config['name']}.");
            $subcrawler = collect();
            foreach ($config['xpaths'] as $xpath) {
                Log::debug("Checking xpath {$xpath}");
                $subcrawler = $crawler->filterXPath($xpath);

                if ($subcrawler->count()) {
                    Log::debug("Found xpath {$xpath}");
                    $this->variantGenerator->addConfig($config['name'], $xpath);
                    break;
                }
            }

            if (!$subcrawler->count()) {
                $missingXpath = implode('\', \'', $config['xpaths']);
                throw new MissingXpathValueException(
                    "Xpath '{$missingXpath}' for field '{$config['name']}' not found in '{$url}'."
                );
            }

            $result['data'][$config['name']] = $subcrawler->each(function ($node) {
                return $node->text();
            });
        }

        Log::info('Calculating variant.');
        $result['variant'] = $this->variantGenerator->getId($config['type']);
        Log::info('Variant calculated.');

        return $result;
    }
}
