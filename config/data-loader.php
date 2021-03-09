<?php declare(strict_types = 1);

/**
 * DataLoader config.
 *
 * @see \App\Services\DataLoader\Client
 */
return [
    /**
     * Enabled?
     */
    'enabled'  => env('DATA_LOADER_ENABLED', false),

    /**
     * GraphQL Endpoint
     */
    'endpoint' => env('DATA_LOADER_ENDPOINT'),

    /**
     * Default limit value.
     */
    'limit'    => env('DATA_LOADER_LIMIT', 100),
];
