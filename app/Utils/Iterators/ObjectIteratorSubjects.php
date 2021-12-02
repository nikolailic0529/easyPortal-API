<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;

/**
 * @template T
 *
 * @mixin \App\Utils\Iterators\ObjectIterator
 */
trait ObjectIteratorSubjects {
    private ?Closure $beforeChunk = null;
    private ?Closure $afterChunk  = null;

    /**
     * Sets the closure that will be called after received each non-empty chunk.
     *
     * @param \Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onBeforeChunk(?Closure $closure): static {
        $this->beforeChunk = $closure;

        return $this;
    }

    /**
     * Sets the closure that will be called after non-empty chunk processed.
     *
     * @param \Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onAfterChunk(?Closure $closure): static {
        $this->afterChunk = $closure;

        return $this;
    }

    /**
     * @param array<T> $items
     */
    protected function chunkLoaded(array $items): void {
        if ($this->beforeChunk && $items) {
            ($this->beforeChunk)($items);
        }
    }

    /**
     * @param array<T> $items
     */
    protected function chunkProcessed(array $items): bool {
        if ($this->afterChunk && $items) {
            ($this->afterChunk)($items);
        }

        return true;
    }
}
