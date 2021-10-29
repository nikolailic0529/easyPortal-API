<?php declare(strict_types = 1);

namespace App\GraphQL\Utils\Iterators;

use Closure;
use EmptyIterator;
use Iterator;

use function array_filter;
use function array_map;
use function min;

/**
 * @template T
 */
abstract class QueryIteratorImpl implements QueryIterator {
    use IteratorProperties;

    /**
     * @param \Closure(array $variables): array<mixed> $executor
     */
    public function __construct(
        protected ?Closure $executor,
        protected ?Closure $retriever = null,
    ) {
        // empty
    }

    /**
     * @return \Iterator<T>
     */
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
            $chunk = $limit ? min($chunk, $limit - $index) : $chunk;
            $items = (array) ($this->executor)($this->getVariables($chunk));
            $items = $this->chunkPrepare($items);

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
    abstract protected function getVariables(int $limit): array;

    /**
     * @param array<mixed> $items
     *
     * @return array<T>
     */
    protected function chunkPrepare(array $items): array {
        return $this->retriever ? array_filter(array_map(function (mixed $item): mixed {
            return ($this->retriever)($item);
        }, $items)) : $items;
    }
}
