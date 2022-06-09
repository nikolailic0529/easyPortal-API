<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Countable;
use Illuminate\Support\Collection;

use function count;

/**
 * @template TItem
 *
 * @extends OneChunkOffsetBasedObjectIterator<TItem>
 */
class ObjectsIterator extends OneChunkOffsetBasedObjectIterator implements Countable {
    /**
     * @param Collection<array-key, TItem>|array<TItem> $items
     */
    public function __construct(
        private Collection|array $items,
    ) {
        parent::__construct(
            function (): array {
                return $this->items instanceof Collection
                    ? $this->items->all()
                    : $this->items;
            },
        );
    }

    // <editor-fold desc="Countable">
    // =========================================================================
    public function count(): int {
        return count($this->items);
    }
    // </editor-fold>
}
