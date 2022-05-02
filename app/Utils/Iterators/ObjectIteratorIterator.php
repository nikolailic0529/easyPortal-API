<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ChunkConverter;
use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\PropertiesProxy;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Iterator;

use function count;

/**
 * @template T
 * @template V
 *
 * @implements ObjectIterator<T>
 */
class ObjectIteratorIterator implements ObjectIterator {
    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\PropertiesProxy<T,V>
     */
    use PropertiesProxy;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\ChunkConverter<T,V>
     */
    use ChunkConverter;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\InitialState<T>
     */
    use InitialState;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\Subjects<T>
     */
    use Subjects;

    /**
     * @param ObjectIterator<V> $internalIterator
     * @param Closure(V): T     $converter
     */
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected ObjectIterator $internalIterator,
        protected Closure $converter,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    /**
     * @return Closure(V): T|null
     */
    protected function getConverter(): ?Closure {
        return $this->converter;
    }

    /**
     * @return ObjectIterator<V>
     */
    protected function getInternalIterator(): ObjectIterator {
        return $this->internalIterator;
    }
    // </editor-fold>

    // <editor-fold desc="IteratorAggregate">
    // =========================================================================
    /**
     * @return Iterator<T>
     */
    public function getIterator(): Iterator {
        try {
            $this->init();

            foreach ($this->getChunks() as $chunk) {
                $chunk = $this->chunkConvert($chunk);

                $this->chunkLoaded($chunk);

                yield from $chunk;

                $this->chunkProcessed($chunk);
            }
        } finally {
            $this->finish();
        }
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    /**
     * @return Iterator<array<V>>
     */
    protected function getChunks(): Iterator {
        // Split sequence into groups
        $index = 0;
        $items = [];
        $chunk = $this->getChunkSize();

        foreach ($this->getInternalIterator() as $key => $item) {
            // Combine items into group
            $items[$key] = $item;

            if (count($items) < $chunk) {
                continue;
            }

            // Process
            yield $index++ => $items;

            // Reset
            $items = [];
        }

        // Tail
        if ($items) {
            yield $index++ => $items;
        }
    }
    //</editor-fold>
}
