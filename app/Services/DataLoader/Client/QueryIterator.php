<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use Closure;
use Generator;
use IteratorAggregate;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_map;
use function array_merge;
use function count;
use function min;

class QueryIterator implements IteratorAggregate {
    protected ?Closure $each = null;

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
        protected ?int $limit = null,
        protected int $offset = 0,
        protected int $chunk = 1000,
    ) {
        // empty
    }

    public function limit(?int $limit): static {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): static {
        $this->offset = $offset;

        return $this;
    }

    public function chunk(int $chunk): static {
        $this->chunk = $chunk;

        return $this;
    }

    /**
     * Sets the closure that will be called after received each chunk.
     */
    public function each(?Closure $each): static {
        $this->each = $each;

        return $this;
    }

    public function getIterator(): Generator {
        $index     = 0;
        $chunk     = $this->limit ? min($this->limit, $this->chunk) : $this->chunk;
        $limit     = $this->limit;
        $offset    = $this->offset;
        $retriever = $this->retriever
            ?: static function (mixed $item) {
                return $item;
            };

        do {
            $items  = $this->client->call($this->selector, $this->graphql, array_merge($this->params, [
                'limit'  => $chunk,
                'offset' => $offset,
            ]));
            $offset = $offset + count($items);
            $items  = array_map(function (mixed $item) use ($retriever): mixed {
                try {
                    return $retriever($item);
                } catch (Throwable $exception) {
                    $this->logger->warning(__METHOD__, [
                        'item'      => $item,
                        'exception' => $exception,
                    ]);

                    throw $exception;
                }
            }, $items);

            if ($this->each) {
                ($this->each)($items);
            }

            foreach ($items as $item) {
                yield $index++ => $item;

                if ($limit && $index >= $limit) {
                    break 2;
                }
            }
        } while ($items);
    }
}
