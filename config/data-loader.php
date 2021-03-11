<?php declare(strict_types = 1);

/**
 * DataLoader config.
 *
 * @see \App\Services\DataLoader\Client\Client
 */

use App\Setting;

return [
    /**
     * Enabled?
     */
    'enabled'  => Setting::get('DATA_LOADER_ENABLED'),

    /**
     * GraphQL Endpoint
     */
    'endpoint' => env('DATA_LOADER_ENDPOINT'),

    /**
     * Default chunk size.
     */
    'chunk'    => Setting::get('DATA_LOADER_CHUNK'),
];
