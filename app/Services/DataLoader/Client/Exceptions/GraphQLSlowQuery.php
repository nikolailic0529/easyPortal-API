<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Exceptions;

use Psr\Log\LogLevel;
use Throwable;

class GraphQLSlowQuery extends ClientException {
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        protected string $query,
        protected array $variables,
        protected float $duration,
        protected float $threshold,
        Throwable $previous = null,
    ) {
        parent::__construct('Slow query detected.', $previous);

        $this->setLevel(LogLevel::NOTICE);
        $this->setContext([
            'query'     => $this->query,
            'variables' => $this->variables,
            'duration'  => $this->duration,
            'threshold' => $this->threshold,
        ]);
    }
}
