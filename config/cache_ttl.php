<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the cache lifetime for different types of data.
    | Values are specified in seconds.
    |
    | - lists: cache lifetime for lists (index methods) - 5 minutes
    | - items: cache lifetime for individual items (show methods) - 10 minutes
    | - workload: cache lifetime for user workload data - 5 minutes
    |
    */

    'lists' => env('CACHE_TTL_LISTS', 300),
    'items' => env('CACHE_TTL_ITEMS', 600),
    'workload' => env('CACHE_TTL_WORKLOAD', 300),
];

