<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use InvalidArgumentException;

use function count;
use function filter_var;
use function gettype;
use function is_int;
use function is_null;
use function sprintf;

use const FILTER_VALIDATE_INT;

/**
 * @template TItem
 *
 * @extends ObjectIteratorImpl<TItem, array{limit: int, offset: int}>
 */
class OffsetBasedObjectIterator extends ObjectIteratorImpl {
    public function setOffset(string|int|null $offset): static {
        if (filter_var($offset, FILTER_VALIDATE_INT) !== false) {
            $offset = (int) $offset;
        }

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
    protected function getChunkVariables(int $limit): array {
        return [
            'limit'  => $limit,
            'offset' => (int) $this->getOffset(),
        ];
    }

    /**
     * @param array<TItem> $items
     */
    protected function chunkProcessed(array $items): bool {
        $this->setOffset((int) $this->getOffset() + count($items));

        return parent::chunkProcessed($items);
    }
}
