<?php

namespace Tests;

use Softonic\LaravelIntelligentScraper\ScraperProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/../src/database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [ScraperProvider::class];
    }
}
