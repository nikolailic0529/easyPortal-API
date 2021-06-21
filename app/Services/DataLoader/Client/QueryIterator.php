<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use Closure;
use Generator;
use IteratorAggregate;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_filter;
use function array_map;
use function array_merge;
use function min;

abstract class QueryIterator implements IteratorAggregate {
    protected ?Closure $beforeChunk = null;
    protected ?Closure $afterChunk  = null;
    protected ?int     $limit       = null;
    protected int      $chunk       = 1000;

    /**
     * @param array<mixed> $params
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
        protected string $selector,
        protected string $graphql,
        protected array $params = [],
        protected ?Closure $retriever = null,
    ) {
        // empty
    }

    public function limit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }

    public function chunk(int $chunk): static {
        $this->chunk = $chunk;

        return $this;
    }

    /**
     * Sets the closure that will be called after received each chunk.
     */
    public function beforeChunk(?Closure $closure): static {
        $this->beforeChunk = $closure;

        return $this;
    }

    /**
     * Sets the closure that will be called after chunk processed.
     */
    public function afterChunk(?Closure $closure): static {
        $this->afterChunk = $closure;

        return $this;
    }

    public function getIterator(): Generator {
        $index = 0;
        $chunk = $this->limit ? min($this->limit, $this->chunk) : $this->chunk;
        $limit = $this->limit;

        do {
            $params = array_merge($this->params, $this->getQueryParams(), [
                'limit' => $chunk,
            ]);
            $items  = (array) $this->client->call($this->selector, $this->graphql, $params);
            $items  = $this->chunkPrepare($items);

            $this->chunkLoaded($items);

            foreach ($items as $item) {
                yield $index++ => $item;

                if ($limit && $index >= $limit) {
                    $this->chunkProcessed($items);

                    break 2;
                }
            }

            $this->chunkProcessed($items);

            if ($this->isLastChunk($items)) {
                break;
            }
        } while ($items);
    }

    /**
     * @return array<string,mixed>
     */
    abstract protected function getQueryParams(): array;

    /**
     * @param array<mixed> $items
     *
     * @return array<mixed>
     */
    protected function chunkPrepare(array $items): array {
        return $this->retriever ? array_filter(array_map(function (mixed $item): mixed {
            try {
                return ($this->retriever)($item);
            } catch (GraphQLRequestFailed $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                $this->logger->error(__METHOD__, [
                    'item'      => $item,
                    'exception' => $exception,
                ]);
            }

            return null;
        }, $items)) : $items;
    }

    /**
     * @param array<mixed> $items
     */
    protected function chunkLoaded(array $items): void {
        try {
            if ($this->beforeChunk) {
                ($this->beforeChunk)($items);
            }
        } catch (Throwable $exception) {
            $this->logger->error(__METHOD__, [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @param array<mixed> $items
     */
    protected function chunkProcessed(array $items): void {
        try {
            if ($this->afterChunk) {
                ($this->afterChunk)($items);
            }
        } catch (Throwable $exception) {
            $this->logger->error(__METHOD__, [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * @param array<mixed> $items
     */
    abstract protected function isLastChunk(array $items): bool;
}
