<?php

if (!function_exists('regexp')) {
    function regexp($regexp)
    {
        return ['regexp' => $regexp];
    }
}

if (!function_exists('scrape')) {
    function scrape($url, $type)
    {
        event(new \Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest($url, $type));
    }
}
