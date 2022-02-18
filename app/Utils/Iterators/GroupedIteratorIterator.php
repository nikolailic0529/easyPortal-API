<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Iterator;

use function count;

/**
 * @template T
 *
 * @implements \App\Utils\Iterators\Contracts\ObjectIterator<array<T>>
 */
class GroupedIteratorIterator implements ObjectIterator {
    /**
     * @param \App\Utils\Iterators\Contracts\ObjectIterator<T> $iterator
     */
    public function __construct(
        private ObjectIterator $iterator,
    ) {
        // empty
    }

    // <editor-fold desc="IteratorAggregate">
    // =========================================================================
    public function getIterator(): Iterator {
        // Split sequence into groups
        $index = 0;
        $items = [];
        $chunk = $this->getChunkSize();

        foreach ($this->iterator as $item) {
            // Combine items into group
            $items[] = $item;

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
    // </editor-fold>

    // <editor-fold desc="ObjectIterator">
    // =========================================================================
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
        $this->iterator->setLimit($limit);

        return $this;
    }

    public function getChunkSize(): int {
        return $this->iterator->getChunkSize();
    }

    public function setChunkSize(?int $chunk): static {
        $this->iterator->setChunkSize($chunk);

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->iterator->getOffset();
    }

    public function setOffset(int|string|null $offset): static {
        $this->iterator->setOffset($offset);

        return $this;
    }

    public function onInit(?Closure $closure): static {
        $this->iterator->onInit($closure);

        return $this;
    }

    public function onFinish(?Closure $closure): static {
        $this->iterator->onFinish($closure);

        return $this;
    }

    public function onBeforeChunk(?Closure $closure): static {
        $this->iterator->onBeforeChunk($closure);

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->iterator->onAfterChunk($closure);

        return $this;
    }
    // </editor-fold>
}
