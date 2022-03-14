<?php declare(strict_types = 1);

use App\Services\Search\Service;

return [
    'hosts'  => [
        env('ELASTIC_HOST', 'localhost:9200'),
    ],
    'logger' => Service::class,
];
