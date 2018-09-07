<?php

function regexp($regexp)
{
    return ['regexp' => $regexp];
}

function scrape($url, $type)
{
    event(new \Softonic\LaravelIntelligentScraper\Scraper\Events\ScrapeRequest($url, $type));
}
