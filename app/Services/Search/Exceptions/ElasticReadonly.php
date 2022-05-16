<?php declare(strict_types = 1);

namespace App\Services\Search\Exceptions;

use App\Services\Search\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

class ElasticReadonly extends ServiceException {
    public function __construct(
        Throwable $previous,
    ) {
        parent::__construct(
            'Elasticsearch in readonly mode!',
            $previous,
        );

        $this->setLevel(LogLevel::CRITICAL);
    }
}
