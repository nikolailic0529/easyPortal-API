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

class OffsetBasedObjectIterator extends ObjectIteratorImpl {
    private ?int $initialOffset = null;

    public function getOffset(): int|null {
        return parent::getOffset();
    }

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

    protected function init(): void {
        $this->initialOffset = $this->getOffset();
    }

    protected function finish(): void {
        $this->setOffset($this->initialOffset);
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
     * @param array<mixed> $items
     */
    protected function chunkProcessed(array $items): bool {
        $this->setOffset($this->getOffset() + count($items));

        return parent::chunkProcessed($items);
    }
}
