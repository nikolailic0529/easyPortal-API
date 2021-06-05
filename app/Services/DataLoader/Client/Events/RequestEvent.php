<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

abstract class RequestEvent {
    /**
     * @param array<mixed> $params
     */
    public function __construct(
        protected string $selector,
        protected string $query,
        protected array $params,
    ) {
        // empty
    }

    public function getSelector(): string {
        return $this->selector;
    }

    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return array<mixed>
     */
    public function getParams(): array {
        return $this->params;
    }
}
