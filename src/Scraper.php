<?php

namespace Softonic\LaravelIntelligentScraper;

use Psr\Log\LoggerInterface;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathFinder;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;
use Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration;

class Scraper
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var XpathFinder
     */
    private $xpathFinder;

    public function __construct(
        Configuration $configuration,
        XpathFinder $xpathFinder,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->xpathFinder   = $xpathFinder;
        $this->logger        = $logger;
    }

    /**
     * @throws \UnexpectedValueException If data cannot be extracted.
     * @throws ConfigurationException    If the scraper configuration cannot be loaded.
     */
    public function getData(string $url, string $type): array
    {
        $this->logger->debug("Loading scrapping configuration for type '$type'");
        $config = $this->loadConfiguration($type);

        return $this->extractData($url, $type, $config);
    }

    private function loadConfiguration(string $type)
    {
        $config = $this->configuration->findByType($type);
        if ($config->isEmpty()) {
            $this->logger->warning("Missing configuration for type '$type'. Calculating...");
            $config = $this->configuration->calculate($type);
        }

        return $config;
    }

    private function extractData(string $url, string $type, $config): array
    {
        try {
            $this->logger->debug("Extracting data from $url for type '$type'");
            return $this->xpathFinder->extract($url, $config);
        } catch (MissingXpathValueException $e) {
            $this->logger->warning(
                "Invalid Configuration for '$url' and type '$type', error: {$e->getMessage()}."
                . ' Calculating configuration.'
            );

            return $this->extractDataWithNewConfig($url, $type);
        }
    }

    private function extractDataWithNewConfig(string $url, string $type): array
    {
        try {
            $config = $this->configuration->calculate($type);

            $this->logger->debug("Extracting data from {$url}");
            $data = $this->xpathFinder->extract($url, $config);

            $config->map->save();

            return $data;
        } catch (MissingXpathValueException $e) {
            throw new \UnexpectedValueException(
                "After calculate configuration, '$url' for type '$type' could not be scraped."
                . " Details: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
