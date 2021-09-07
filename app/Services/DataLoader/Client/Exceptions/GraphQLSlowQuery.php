<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use Psr\Log\LogLevel;
use Throwable;

class GraphQLSlowQuery extends ClientException {
    /**
     * @param array<mixed> $params
     */
    public function __construct(
        protected string $query,
        protected array $params,
        protected float $duration,
        protected float $threshold,
        Throwable $previous = null,
    ) {
        parent::__construct('Slow query detected.', $previous);

        $this->setLevel(LogLevel::NOTICE);
        $this->setContext([
            'query'     => $this->query,
            'params'    => $this->params,
            'duration'  => $this->duration,
            'threshold' => $this->threshold,
        ]);
    }
}
