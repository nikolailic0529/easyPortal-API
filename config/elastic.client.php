<?php declare(strict_types = 1);

use App\Services\Search\Service;

return [
    'default'     => env('ELASTIC_CONNECTION', 'default'),
    'connections' => [
        'default' => [
            'hosts'               => [
                env('EP_SEARCH_URL', env('ELASTIC_HOST', 'localhost:9200')),
            ],
            'logger'              => Service::class,
            'httpClientOptions'   => [
                'timeout' => 60,
            ],
            'basicAuthentication' => env('EP_SEARCH_USERNAME') && env('EP_SEARCH_PASSWORD')
                ? [env('EP_SEARCH_USERNAME'), env('EP_SEARCH_PASSWORD')]
                : null,
        ],
    ],
];
