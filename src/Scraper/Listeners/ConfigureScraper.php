<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Softonic\LaravelIntelligentScraper\Scraper\Application\XpathFinder;
use Softonic\LaravelIntelligentScraper\Scraper\Events\InvalidConfiguration;
use Softonic\LaravelIntelligentScraper\Scraper\Events\Scraped;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeFailed;
use Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\ConfigurationException;
use Softonic\LaravelIntelligentScraper\Scraper\Exceptions\MissingXpathValueException;
use Softonic\LaravelIntelligentScraper\Scraper\Repositories\Configuration;

class ConfigureScraper implements ShouldQueue
{
    /**
     * Specific queue for configure scrapper.
     *
     * @var string
     */
    public $queue = 'configure';

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

    public function handle(InvalidConfiguration $invalidConfiguration)
    {
        try {
            $scrapeRequest = $invalidConfiguration->scrapeRequest;
            $config        = $this->configuration->calculate($scrapeRequest->type);
            $this->extractData($scrapeRequest, $config);
            $config->map->save();
        } catch (MissingXpathValueException $e) {
            $this->logger->warning(
                "Configuration not available for '$scrapeRequest->url' and type '$scrapeRequest->type', error: {$e->getMessage()}."
            );
            event(new ScrapeFailed($invalidConfiguration->scrapeRequest));
        } catch (\UnexpectedValueException $e) {
            $this->scrapeFailed($invalidConfiguration, $scrapeRequest, $e);
        } catch (ConfigurationException $e) {
            $this->scrapeFailed($invalidConfiguration, $scrapeRequest, $e);
        }
    }

    /**
     * @param ScrapeRequest $scrapeRequest
     * @param               $config
     */
    private function extractData(ScrapeRequest $scrapeRequest, $config): void
    {
        $this->logger->debug("Extracting data from $scrapeRequest->url for type '$scrapeRequest->type'");

        $data = $this->xpathFinder->extract($scrapeRequest->url, $config);
        event(new Scraped($scrapeRequest, $data));
    }

    /**
     * @param InvalidConfiguration $invalidConfiguration
     * @param                      $scrapeRequest
     * @param                      $e
     */
    private function scrapeFailed(InvalidConfiguration $invalidConfiguration, $scrapeRequest, $e): void
    {
        $this->logger->error(
            "Error scraping '{$scrapeRequest->url}'",
            ['message' => $e->getMessage()]
        );
        event(new ScrapeFailed($invalidConfiguration->scrapeRequest));
    }
}
