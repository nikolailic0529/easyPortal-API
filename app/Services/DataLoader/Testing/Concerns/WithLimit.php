<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Concerns;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\ClientIterator;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;

/**
 * @mixin Client
 */
trait WithLimit {
    private ?int $limit = null;

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @template T
     *
     * @param array<string,mixed>      $variables
     * @param Closure(array<mixed>): T $retriever
     *
     * @return ObjectIterator<T>
     */
    public function getOffsetBasedIterator(
        string $selector,
        string $graphql,
        array $variables,
        Closure $retriever,
        int $limit = null,
        string|int|null $offset = null,
    ): ObjectIterator {
        return (new ClientIterator(parent::getOffsetBasedIterator(
            $selector,
            $graphql,
            $variables,
            $retriever,
            $limit,
            $offset,
        )))
            ->setTestsLimit($this->getLimit());
    }

    /**
     * @template T
     *
     * @param array<string,mixed>      $variables
     * @param Closure(array<mixed>): T $retriever
     *
     * @return ObjectIterator<T>
     */
    public function getLastIdBasedIterator(
        string $selector,
        string $graphql,
        array $variables,
        Closure $retriever,
        int $limit = null,
        string $lastId = null,
    ): ObjectIterator {
        return (new ClientIterator(parent::getLastIdBasedIterator(
            $selector,
            $graphql,
            $variables,
            $retriever,
            $limit,
            $lastId,
        )))
            ->setTestsLimit($this->getLimit());
    }
}
