<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use function array_slice;

class OneChunkOffsetBasedObjectIterator extends OffsetBasedObjectIterator {
    /**
     * @var array<mixed>
     */
    protected array $items;

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

    /**
     * @inheritDoc
     */
    protected function getChunkVariables(int $limit): array {
        return [
            // All data loaded by one query so there are no reasons to return any variables.
        ];
    }
}
