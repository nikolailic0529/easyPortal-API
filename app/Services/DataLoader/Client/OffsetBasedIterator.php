<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use InvalidArgumentException;

use function count;
use function gettype;
use function is_int;
use function is_null;
use function sprintf;

class OffsetBasedIterator extends QueryIterator {
    public function getOffset(): int|null {
        return parent::getOffset();
    }

    public function setOffset(string|int|null $offset): static {
        if (!is_int($offset) && !is_null($offset)) {
            throw new InvalidArgumentException(sprintf(
                'The `$offset` must be `int` or `null`, `%s` given',
                gettype($offset),
            ));
        }

        return parent::setOffset($offset);
    }

    /**
     * @inheritDoc
     */
    protected function getQueryParams(): array {
        return [
            'offset' => $this->getOffset(),
        ];
    }

    /**
     * @param array<mixed> $items
     */
    protected function chunkProcessed(array $items): bool {
        $this->setOffset($this->getOffset() + count($items));

        return parent::chunkProcessed($items);
    }
}
