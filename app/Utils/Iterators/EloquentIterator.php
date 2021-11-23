<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Generator;
use LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator as LaraASPIterator;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 *
 * @implements \App\Utils\Iterators\ObjectIterator<T>
 */
class EloquentIterator implements ObjectIterator {
    /**
     * @param \LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator<T> $iterator
     */
    public function __construct(
        protected LaraASPIterator $iterator,
    ) {
        // empty
    }

    public function getIterator(): Generator {
        return $this->iterator->getIterator();
    }

    public function getIndex(): int {
        return $this->iterator->getIndex();
    }

    public function setIndex(int $index): static {
        $this->iterator->setIndex($index);

        return $this;
    }

    public function getLimit(): ?int {
        return $this->iterator->getLimit();
    }

    public function setLimit(?int $limit): static {
        $this->iterator->setIndex($limit);

        return $this;
    }

    public function getChunkSize(): int {
        return $this->iterator->getChunkSize();
    }

    public function setChunkSize(int $chunk): static {
        $this->iterator->setChunkSize($chunk);

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->getOffset();
    }

    public function setOffset(int|string|null $offset): static {
        $this->setOffset($offset);

        return $this;
    }

    public function onBeforeChunk(?Closure $closure): static {
        $this->iterator->onAfterChunk($closure);

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->iterator->onAfterChunk($closure);

        return $this;
    }
}
