<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client\Events;

abstract class RequestEvent {
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        protected string $selector,
        protected string $query,
        protected array $variables,
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
     * @return array<string, mixed>
     */
    public function getVariables(): array {
        return $this->variables;
    }
}
