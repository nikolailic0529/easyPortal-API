<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\PropertiesProxy;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Iterator;

use function count;

/**
 * @template T
 *
 * @implements ObjectIterator<array<T>>
 */
class GroupedIteratorIterator implements ObjectIterator {
    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\PropertiesProxy<T,T>
     */
    use PropertiesProxy;

    /**
     * @param ObjectIterator<T> $internalIterator
     */
    public function __construct(
        private ObjectIterator $internalIterator,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getInternalIterator(): ObjectIterator {
        return $this->internalIterator;
    }
    // </editor-fold>

    // <editor-fold desc="IteratorAggregate">
    // =========================================================================
    public function getIterator(): Iterator {
        // Split sequence into groups
        $index = 0;
        $items = [];
        $chunk = $this->getChunkSize();

        foreach ($this->internalIterator as $key => $item) {
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
    // </editor-fold>

    // <editor-fold desc="ObjectIterator">
    // =========================================================================
    public function onInit(?Closure $closure): static {
        $this->getInternalIterator()->onInit($closure);

        return $this;
    }

    public function onFinish(?Closure $closure): static {
        $this->getInternalIterator()->onFinish($closure);

        return $this;
    }

    public function onBeforeChunk(?Closure $closure): static {
        $this->getInternalIterator()->onBeforeChunk($closure);

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->getInternalIterator()->onAfterChunk($closure);

        return $this;
    }
    // </editor-fold>
}
