<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ChunkConverter;
use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\Subjects;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Iterator;

/**
 * @template T
 * @template V
 *
 * @implements \App\Utils\Iterators\ObjectIterator<V>
 *
 * @uses \App\Utils\Iterators\Concerns\InitialState<T>
 * @uses \App\Utils\Iterators\Concerns\ChunkConverter<T,V>
 */
class ObjectIteratorIterator implements ObjectIterator {
    use ChunkConverter;
    use InitialState;
    use Subjects;

    /**
     * @param \App\Utils\Iterators\ObjectIterator<V> $iterator
     * @param \Closure(V $item): T                   $converter
     */
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected ObjectIterator $iterator,
        protected Closure $converter,
    ) {
        // empty
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    protected function getConverter(): ?Closure {
        return $this->converter;
    }

    /**
     * @return \Iterator<T>
     */
    public function getIterator(): Iterator {
        try {
            $this->init();

            $chunk    = [];
            $iterator = (clone $this->iterator)
                ->onBeforeChunk(function (array $items) use (&$chunk): void {
                    $chunk = $this->chunkConvert($items);

                    $this->chunkLoaded($chunk);
                })
                ->onAfterChunk(function () use (&$chunk): void {
                    $this->chunkProcessed($chunk);

                    $chunk = null;
                });

            foreach ($iterator as $key => $item) {
                if (isset($chunk[$key])) {
                    yield $key => $chunk[$key];
                }
            }
        } finally {
            $this->finish();
        }
    }

    // <editor-fold desc="Proxy">
    // =========================================================================
    public function getIndex(): int {
        return $this->iterator->getIndex();
    }

    /**
     * @return $this<T,V>
     */
    public function setIndex(int $index): static {
        $this->iterator->setIndex($index);

        return $this;
    }

    public function getLimit(): ?int {
        return $this->iterator->getLimit();
    }

    /**
     * @return $this<T,V>
     */
    public function setLimit(?int $limit): static {
        $this->iterator->setLimit($limit);

        return $this;
    }

    public function getChunkSize(): int {
        return $this->iterator->getChunkSize();
    }

    /**
     * @return $this<T,V>
     */
    public function setChunkSize(?int $chunk): static {
        $this->iterator->setChunkSize($chunk);

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->iterator->getOffset();
    }

    /**
     * @return $this<T,V>
     */
    public function setOffset(int|string|null $offset): static {
        $this->iterator->setOffset($offset);

        return $this;
    }
    // </editor-fold>
}
