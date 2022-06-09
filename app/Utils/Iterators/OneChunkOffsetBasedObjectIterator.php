<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use function array_slice;

/**
 * @template TItem
 *
 * @extends OffsetBasedObjectIterator<TItem>
 */
class OneChunkOffsetBasedObjectIterator extends OffsetBasedObjectIterator {
    /**
     * @var array<TItem>
     */
    private array $items;

    /**
     * @inheritDoc
     */
    protected function getChunk(int $limit): array {
        // Load items
        if (!isset($this->items)) {
            $this->items = parent::getChunk(-1);
        }

        // Return chunk
        return array_slice($this->items, (int) $this->getOffset(), $limit);
    }
}
