<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use function array_merge;

/**
 * @template V
 */
class Query {
    /**
     * @var array<string,mixed>|null
     */
    protected ?array $variables = null;

    /**
     * @param array<string,mixed> $params
     */
    public function __construct(
        protected Client $client,
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
     * @return array<string,mixed>
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getVariables(): ?array {
        return $this->variables;
    }

    /**
     * @param array<string,mixed> $variables
     *
     * @return array<V>
     */
    public function __invoke(array $variables): array {
        $this->variables = $variables;
        $params          = array_merge($this->params, $variables);
        $result          = $this->client->call($this->selector, $this->query, $params);

        return $result;
    }
}
