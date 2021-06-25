<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use Closure;
use EmptyIterator;
use Iterator;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_filter;
use function array_map;
use function array_merge;
use function min;

abstract class QueryIteratorImpl implements QueryIterator {
    protected ?Closure        $beforeChunk = null;
    protected ?Closure        $afterChunk  = null;
    protected ?int            $limit       = null;
    protected int             $chunk       = 1000;
    protected string|int|null $offset      = null;

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

    public function getLimit(): ?int {
        return $this->limit;
    }

    public function setLimit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }

    public function getChunkSize(): int {
        return $this->chunk;
    }

    public function setChunkSize(int $chunk): static {
        $this->chunk = $chunk;

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->offset;
    }

    public function setOffset(string|int|null $offset): static {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Sets the closure that will be called after received each chunk.
     */
    public function onBeforeChunk(?Closure $closure): static {
        $this->beforeChunk = $closure;

        return $this;
    }

    /**
     * Sets the closure that will be called after chunk processed.
     */
    public function onAfterChunk(?Closure $closure): static {
        $this->afterChunk = $closure;

        return $this;
    }

    public function getIterator(): Iterator {
        // Prepare
        $index = 0;
        $chunk = $this->limit ? min($this->limit, $this->chunk) : $this->chunk;
        $limit = $this->limit;

        // Limit?
        if ($limit === 0) {
            return new EmptyIterator();
        }

        // Iterate
        do {
            $chunk  = $limit ? min($chunk, $limit - $index) : $chunk;
            $params = array_merge($this->params, $this->getQueryParams(), [
                'limit' => $chunk,
            ]);
            $items  = (array) $this->client->call($this->selector, $this->graphql, $params);
            $items  = $this->chunkPrepare($items);

            $this->chunkLoaded($items);

            foreach ($items as $item) {
                yield $index++ => $item;
            }

            if (!$this->chunkProcessed($items) || ($limit && $index >= $limit)) {
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
    protected function chunkProcessed(array $items): bool {
        try {
            if ($this->afterChunk) {
                ($this->afterChunk)($items);
            }
        } catch (Throwable $exception) {
            $this->logger->error(__METHOD__, [
                'exception' => $exception,
            ]);
        }

        return true;
    }
}