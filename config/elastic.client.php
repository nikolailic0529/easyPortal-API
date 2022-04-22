<?php declare(strict_types = 1);

use App\Services\Search\Service;

return [
    // May overwrite other options => should be on the top
    'connectionParams'    => [
        'client' => [
            'connect_timeout' => 60,
        ],
    ],

    // Options
    'hosts'               => [
        env('EP_SEARCH_URL', env('ELASTIC_HOST', 'localhost:9200')),
    ],
    'logger'              => Service::class,
    'basicAuthentication' => env('EP_SEARCH_USERNAME') && env('EP_SEARCH_PASSWORD')
        ? [env('EP_SEARCH_USERNAME'), env('EP_SEARCH_PASSWORD')]
        : null,
];
