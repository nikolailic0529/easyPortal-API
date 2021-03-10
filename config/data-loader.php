<?php declare(strict_types = 1);

/**
 * DataLoader config.
 *
 * @see \App\Services\DataLoader\Client\Client
 */
return [
    /**
     * Enabled?
     */
    'enabled'  => env('DATA_LOADER_ENABLED', DATA_LOADER_ENABLED ?? false),

    /**
     * GraphQL Endpoint
     */
    'endpoint' => env('DATA_LOADER_ENDPOINT'),

    /**
     * Default chunk size.
     */
    'chunk'    => env('DATA_LOADER_CHUNK', DATA_LOADER_CHUNK ?? 100),
];
