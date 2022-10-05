<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing;

use App\Utils\Iterators\ObjectIteratorIterator;

/**
 * @template TItem
 *
 * @extends ObjectIteratorIterator<TItem, TItem>
 */
class ClientIterator extends ObjectIteratorIterator {
    // <editor-fold desc="Getters & Setters">
    // =========================================================================
    public function setLimit(?int $limit): static {
        return $this;
    }

    public function setTestsLimit(?int $limit): static {
        parent::setLimit($limit);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Convert">
    // =========================================================================
    /**
     * @inheritdoc
     */
    protected function chunkConvert(array $items): array {
        return $items;
    }
    // </editor-fold>
}
