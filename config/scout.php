<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Scout Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default search driver that will be used when
    | calling `search` on a model. Supported: "algolia", "meilisearch",
    | "null", "collection".
    |
    */
    'driver' => env('SCOUT_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | You may specify a prefix that will be applied to all of your search
    | indexes. This is useful for separating indexes between environments.
    |
    */
    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | When this option is true, all operations that update the search index
    | will be queued. This allows your application to continue processing
    | without waiting for the index to be refreshed.
    |
    */
    'queue' => env('SCOUT_QUEUE', false),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY', null),
    ],
];
