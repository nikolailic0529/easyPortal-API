<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Services\Search\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

class ElasticUnavailable extends ServiceException {
    public function __construct(
        Throwable $previous,
    ) {
        parent::__construct(
            'Elasticsearch unavailable!',
            $previous,
        );

        $this->setLevel(LogLevel::CRITICAL);
    }
}
