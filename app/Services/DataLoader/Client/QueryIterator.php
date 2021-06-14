<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use Closure;
use Generator;
use IteratorAggregate;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_map;
use function array_merge;
use function min;

abstract class QueryIterator implements IteratorAggregate {
    protected ?Closure $each  = null;
    protected ?int     $limit = null;
    protected int      $chunk = 1000;

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
    public function each(?Closure $each): static {
        $this->each = $each;

        return $this;
    }

    public function getIterator(): Generator {
        $index     = 0;
        $chunk     = $this->limit ? min($this->limit, $this->chunk) : $this->chunk;
        $limit     = $this->limit;
        $retriever = $this->retriever
            ?: static function (mixed $item) {
                return $item;
            };

        do {
            $params = array_merge($this->params, $this->getQueryParams(), [
                'limit' => $chunk,
            ]);
            $items  = (array) $this->client->call($this->selector, $this->graphql, $params);
            $items  = array_map(function (mixed $item) use ($retriever): mixed {
                try {
                    return $retriever($item);
                } catch (GraphQLRequestFailed $exception) {
                    throw $exception;
                } catch (Throwable $exception) {
                    $this->logger->warning(__METHOD__, [
                        'item'      => $item,
                        'exception' => $exception,
                    ]);

                    throw $exception;
                }
            }, $items);

            $this->chunkLoaded($items);

            foreach ($items as $item) {
                yield $index++ => $item;

                if ($limit && $index >= $limit) {
                    break 2;
                }
            }

            if (!$this->chunkProcessed($items)) {
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
     */
    protected function chunkLoaded(array $items): void {
        if ($this->each) {
            ($this->each)($items);
        }
    }

    /**
     * @param array<mixed> $items
     */
    abstract protected function chunkProcessed(array $items): bool;
}
