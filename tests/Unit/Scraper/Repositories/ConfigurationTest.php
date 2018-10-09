<?php

namespace Softonic\LaravelIntelligentScraper\Scraper\Repositories;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Softonic\LaravelIntelligentScraper\Scraper\Application\Configurator;
use Softonic\LaravelIntelligentScraper\Scraper\Models\Configuration as ConfigurationModel;
use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function whenRetrieveAllConfigurationItShouldReturnIt()
    {
        ConfigurationModel::create([
            'name'   => 'title',
            'type'   => 'post',
            'xpaths' => '//*[@id="title"]',
        ]);
        ConfigurationModel::create([
            'name'   => 'category',
            'type'   => 'list',
            'xpaths' => '//*[@id="category"]',
        ]);
        ConfigurationModel::create([
            'name'   => 'author',
            'type'   => 'post',
            'xpaths' => '//*[@id="author"]',
        ]);

        $configurator = \Mockery::mock(Configurator::class);

        $configuration = new Configuration($configurator);
        $data          = $configuration->findByType('post');

        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function whenRecalculateButThereIsNotApostDatasetItShouldThrowAnException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('A dataset example is needed to recalculate xpaths for type post.');

        $configurator = \Mockery::mock(Configurator::class);

        $configuration = new Configuration($configurator);
        $configuration->calculate('post');
    }

    /**
     * @test
     */
    public function whenRecalculateItShouldStoreTheNewXpaths()
    {
        ScrapedDataset::create([
            'url'  => 'https://test.c/123456789222',
            'type' => 'post',
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data' => [
                'title'  => 'My first post',
                'author' => 'Jhon Doe',
            ],
        ]);
        ScrapedDataset::create([
            'url'  => 'https://test.c/7675487989076',
            'type' => 'list',
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data' => [
                'cateogry' => 'Entertaiment',
                'author'   => 'Jhon Doe',
            ],
        ]);
        ScrapedDataset::create([
            'url'  => 'https://test.c/223456789111',
            'type' => 'post',
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data' => [
                'title'  => 'My second Post',
                'author' => 'Jhon Doe',
            ],
        ]);

        $config = collect([
            ConfigurationModel::make([
                'name'   => 'title',
                'type'   => 'post',
                'xpaths' => '//*[@id="title"]',
            ]),
            ConfigurationModel::make([
                'name'   => 'author',
                'type'   => 'post',
                'xpaths' => '//*[@id="author"]',
            ]),
        ]);

        Cache::shouldReceive('get')
            ->with(Configuration::class . '-config-post')
            ->andReturnNull();
        Cache::shouldReceive('put')
            ->with(Configuration::class . '-config-post', $config, Configuration::CACHE_TTL);

        $configurator = \Mockery::mock(Configurator::class);
        $configurator->shouldReceive('configureFromDataset')
            ->withArgs(function ($posts) {
                return 2 == $posts->count();
            })
            ->andReturn($config);

        $configuration = new Configuration($configurator);
        $configs       = $configuration->calculate('post');

        $this->assertEquals($configs[0]['name'], 'title');
        $this->assertEquals($configs[0]['type'], 'post');
        $this->assertEquals($configs[0]['xpaths'], '//*[@id="title"]');
        $this->assertEquals($configs[1]['name'], 'author');
        $this->assertEquals($configs[1]['type'], 'post');
        $this->assertEquals($configs[1]['xpaths'], '//*[@id="author"]');
    }

    /**
     * @test
     */
    public function whenRecalculateFailsItShouldThrowAnException()
    {
        ScrapedDataset::create([
            'url'  => 'https://test.c/123456789222',
            'type' => 'post',
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data' => [
                'title'  => 'My first post',
                'author' => 'Jhon Doe',
            ],
        ]);
        ScrapedDataset::create([
            'url'  => 'https://test.c/7675487989076',
            'type' => 'list',
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data' => [
                'cateogry' => 'Entertaiment',
                'author'   => 'Jhon Doe',
            ],
        ]);
        ScrapedDataset::create([
            'url'  => 'https://test.c/223456789111',
            'type' => 'post',
            'variant' => 'b265521fc089ac61b794bfa3a5ce8a657f6833ce',
            'data' => [
                'title'  => 'My second post',
                'author' => 'Jhon Doe',
            ],
        ]);

        Cache::shouldReceive('get')
            ->with(Configuration::class . '-config-post')
            ->andReturnNull();

        $configurator = \Mockery::mock(Configurator::class);
        $configurator->shouldReceive('configureFromDataset')
            ->withArgs(function ($posts) {
                return 2 == $posts->count();
            })
            ->andThrow(new \UnexpectedValueException('Recalculate fail'));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Recalculate fail');

        $configuration = new Configuration($configurator);
        $configuration->calculate('post');
    }

    /**
     * @test
     */
    public function whenCalculateAfterAnotherCalculateItShouldUseThePrecalclatedConfig()
    {
        $configurator = \Mockery::mock(Configurator::class);
        $configurator->shouldReceive('configureFromDataset')
            ->never();

        $config = collect('configuration');

        Cache::shouldReceive('get')
            ->with(Configuration::class . '-config-post')
            ->andReturn($config);

        $configuration = new Configuration($configurator);
        $this->assertEquals(
            $config,
            $configuration->calculate('post')
        );
    }
}
