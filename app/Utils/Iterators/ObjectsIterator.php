<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Countable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;

use function count;

/**
 * @template T
 * @template V
 *
 * @extends OneChunkOffsetBasedObjectIterator<T, V>
 */
class ObjectsIterator extends OneChunkOffsetBasedObjectIterator implements Countable {
    /**
     * @param Collection<array-key, V>|array<V> $items
     * @param Closure(V):T|null                 $converter
     */
    public function __construct(
        ExceptionHandler $exceptionHandler,
        private Collection|array $items,
        ?Closure $converter = null,
    ) {
        parent::__construct(
            $exceptionHandler,
            function (): array {
                return $this->items instanceof Collection
                    ? $this->items->all()
                    : $this->items;
            },
            $converter,
        );
    }

    // <editor-fold desc="Countable">
    // =========================================================================
    public function count(): int {
        return count($this->items);
    }
    // </editor-fold>
}
