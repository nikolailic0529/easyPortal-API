<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use EmptyIterator;
use Iterator;

use function array_filter;
use function array_map;
use function end;
use function min;
use function reset;

/**
 * @template T
 */
abstract class ObjectIteratorImpl implements ObjectIterator {
    use ObjectIteratorProperties;

    /**
     * @var array{array<mixed>,array<mixed>}
     */
    private array $previous = [];

    /**
     * @param \Closure(array $variables): array<mixed> $executor
     * @param \Closure(mixed $item): T                 $retriever
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
        $this->init();

        try {
            do {
                $chunk = $limit ? min($chunk, $limit - $index) : $chunk;
                $items = $this->getChunk($chunk);
                $items = $this->chunkPrepare($items);

                $this->chunkLoaded($items);

                foreach ($items as $item) {
                    yield $index++ => $item;
                }

                if (!$this->chunkProcessed($items) || ($limit && $index >= $limit)) {
                    break;
                }
            } while ($items);
        } finally {
            $this->finish();
        }
    }

    protected function init(): void {
        // empty
    }

    protected function finish(): void {
        // empty
    }

    /**
     * @param array<string,mixed> $variables
     *
     * @return array<mixed>
     */
    protected function execute(array $variables): array {
        return (array) ($this->executor)($variables);
    }

    /**
     * @return array<mixed>
     */
    protected function getChunk(int $limit): array {
        return $this->execute($this->getChunkVariables($limit));
    }

    /**
     * @return array<string,mixed>
     */
    abstract protected function getChunkVariables(int $limit): array;

    /**
     * @param array<mixed> $items
     *
     * @return array<T>
     */
    protected function chunkPrepare(array $items): array {
        // Infinite loop?
        $current        = [end($items), reset($items)];
        $items          = $this->previous && $current === $this->previous ? [] : $items;
        $this->previous = $current;

        // Convert
        return $this->retriever ? array_filter(array_map(function (mixed $item): mixed {
            return $this->chunkPrepareItem($this->retriever, $item);
        }, $items)) : $items;
    }

    /**
     * @param \Closure(mixed $item): T $retriever
     *
     * @return T
     */
    protected function chunkPrepareItem(Closure $retriever, mixed $item): mixed {
        return $retriever($item);
    }
}
