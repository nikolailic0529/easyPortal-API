<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use function count;

class OffsetBasedIterator extends QueryIterator {
    protected int $offset = 0;

    public function getOffset(): int {
        return $this->offset;
    }

    public function offset(int $offset): static {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function getQueryParams(): array {
        return [
            'offset' => $this->offset,
        ];
    }

    /**
     * @param array<mixed> $items
     */
    protected function chunkProcessed(array $items): bool {
        $this->offset($this->offset + count($items));

        return parent::chunkProcessed($items);
    }
}
