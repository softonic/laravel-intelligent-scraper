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
     * Configure listener per Scraped type
     *
     * Format:
     *      [
     *          'type' => 'handler class',
     *      ].
     *
     * Example:
     *      [
     *          'news' => App\NewsHandler::class,
     *          'post' => App\PostHandler::class
     *      ]
     */
    'listeners' => [
        //
    ],
];
