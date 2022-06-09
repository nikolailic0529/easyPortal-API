<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\Properties;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Exceptions\InfiniteLoopDetected;
use Closure;
use EmptyIterator;
use Iterator;

use function end;
use function min;
use function reset;

/**
 * @template TItem
 * @template TVariables of array<string,mixed>
 *
 * @implements ObjectIterator<TItem>
 */
abstract class ObjectIteratorImpl implements ObjectIterator {
    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\Subjects<TItem>
     */
    use Subjects;
    use Properties;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\InitialState<TItem>
     */
    use InitialState;

    /**
     * @var array{?TItem,?TItem}|null
     */
    private ?array $previous = null;

    /**
     * @param Closure(TVariables): array<TItem> $executor
     */
    public function __construct(
        protected Closure $executor,
    ) {
        // empty
    }

    /**
     * @return Iterator<TItem>
     */
    public function getIterator(): Iterator {
        // Prepare
        $index = $this->getIndex();
        $limit = $this->getLimit();
        $chunk = $limit ? min($limit, $this->getChunkSize()) : $this->getChunkSize();

        // Limit?
        if ($limit === 0) {
            return new EmptyIterator();
        }

        // Iterate
        try {
            $this->init();

            do {
                $chunk = $limit ? min($chunk, $limit - $index) : $chunk;
                $items = $this->getChunk($chunk);
                $items = $this->chunkPrepare($items);

                $this->chunkLoaded($items);

                foreach ($items as $item) {
                    yield $index++ => $item;

                    $this->setIndex($index);
                }

                if (!$this->chunkProcessed($items) || ($limit && $index >= $limit)) {
                    break;
                }
            } while ($items);
        } finally {
            $this->finish();
        }
    }

    /**
     * @param TVariables $variables
     *
     * @return array<TItem>
     */
    protected function execute(array $variables): array {
        return ($this->executor)($variables);
    }

    /**
     * @return array<TItem>
     */
    protected function getChunk(int $limit): array {
        return $this->execute($this->getChunkVariables($limit));
    }

    /**
     * @return TVariables
     */
    abstract protected function getChunkVariables(int $limit): array;

    /**
     * @param array<TItem> $items
     *
     * @return array<TItem>
     */
    protected function chunkPrepare(array $items): array {
        // Infinite loop?
        $current        = [$items ? end($items) : null, $items ? reset($items) : null];
        $previous       = $this->previous;
        $this->previous = $current;

        if ($previous && $current === $previous) {
            throw new InfiniteLoopDetected($this::class);
        }

        // Return
        return $items;
    }
}
