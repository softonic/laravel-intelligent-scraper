<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Application;

use Goutte\Client as GoutteClient;
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
        $crawler  = $this->client->request('GET', $url);
        $httpCode = $this->client->getInternalResponse()->getStatus();
        if ($httpCode !== 200) {
            throw new \UnexpectedValueException("Response error from '{$url}' with '{$httpCode}' http code");
        }

        $result = [];
        foreach ($configs as $config) {
            $subcrawler = collect();
            foreach ($config['xpaths'] as $xpath) {
                $subcrawler = $crawler->filterXPath($xpath);

                if ($subcrawler->count()) {
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

        $result['variant'] = $this->variantGenerator->getId($config['type']);

        return $result;
    }
}
