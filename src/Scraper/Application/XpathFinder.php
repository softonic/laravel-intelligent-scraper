<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration;

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

    /**
     * @param string          $url
     * @param Configuration[] $configs
     *
     * @return array
     */
    public function extract(string $url, $configs): array
    {
        $crawler = $this->getCrawler($url);

        Log::info('Response Received. Start crawling.');
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

    private function getCrawler(string $url)
    {
        try {
            Log::info("Requesting $url");

            return $this->client->request('GET', $url);
        } catch (ConnectException $e) {
            Log::info("Unavailable url '{$url}'", ['message' => $e->getMessage()]);
            throw new \UnexpectedValueException("Unavailable url '{$url}'");
        } catch (RequestException $e) {
            $httpCode = $e->getResponse()->getStatusCode();
            Log::info('Invalid response http status', ['status' => $httpCode]);
            throw new \UnexpectedValueException("Response error from '{$url}' with '{$httpCode}' http code");
        }
    }
}
