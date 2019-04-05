<?php

return [
    /*
     * Configurations related with XPATH
     */
    'xpath' => [
        /*
         * When the scraping is trying to get the best xpath it needs sometimes to
         * ignore some elements identifiers because they are randomized like
         * for example the react_xxxxx identifiers that are managed
         * by the reactJS framework.
         */
        'ignore-identifiers' => '/^react_.*$/',
    ],
    /*
     * Configure listener per event type
     *
     * Format:
     *      [
     *          'type' => 'handler class',
     *      ].
     *
     * Declare in the "scraped" key the listener you want to attend the scrapes done successfully.
     * Declare in the "scrape-failed" key the listener you want to attend the scrapes that could not retrieve the
     * information.
     *
     * Example:
     * 'scraped' => [
     *       'post' => App\CreatePostHandler::class
     *   ],
     *   'scrape-failed' => [
     *       'post' => App\NotifyErrorHandler::class
     *   ]
     */
    'listeners' => [
        'scraped' => [
            //
        ],
        'scrape-failed' => [
            //
        ],
    ],
];
