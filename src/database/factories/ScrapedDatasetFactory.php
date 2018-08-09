<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Softonic\LaravelIntelligentScraper\Scraper\Models\ScrapedDataset;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(ScrapedDataset::class, function (Faker\Generator $faker) {
    return [
        'url'  => $faker->url . $faker->randomDigit,
        'type' => 'post',
        'data' => [
            'title'  => $faker->word,
            'author' => $faker->word,
        ],
    ];
});
