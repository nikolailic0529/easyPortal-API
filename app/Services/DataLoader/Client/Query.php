<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\GraphQL\GraphQL;

use function array_merge;

/**
 * @template V
 */
class Query {
    /**
     * @var array<string,mixed>|null
     */
    protected ?array $lastVariables = null;

    /**
     * @param array<string,mixed> $variables
     */
    public function __construct(
        protected Client $client,
        protected GraphQL $query,
        protected array $variables,
    ) {
        // empty
    }

    public function getSelector(): string {
        return $this->getQuery()->getSelector();
    }

    public function getQuery(): GraphQL {
        return $this->query;
    }

    /**
     * @return array<string,mixed>
     */
    public function getVariables(): array {
        return $this->variables;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getLastVariables(): ?array {
        return $this->lastVariables;
    }

    /**
     * @param array<string,mixed> $variables
     *
     * @return array<V>
     */
    public function __invoke(array $variables): array {
        $this->lastVariables = $variables;
        $variables           = array_merge($this->variables, $variables);
        $result              = $this->client->call(
            "data.{$this->query->getSelector()}",
            (string) $this->query,
            $variables,
        );

        return $result;
    }
}
