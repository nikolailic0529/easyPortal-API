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

/**
 * @template T
 * @template V
 *
 * @implements ObjectIterator<V>
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

    protected function getConverter(): ?Closure {
        return $this->converter;
    }

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

            $iterator = clone $this->getInternalIterator();
            $iterator = (new GroupedIteratorIterator($iterator))
                ->setChunkSize($this->getChunkSize());

            foreach ($iterator as $chunk) {
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
}
