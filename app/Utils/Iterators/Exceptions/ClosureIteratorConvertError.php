<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Exceptions;

use Throwable;

/**
 * @template TItem
 */
class ClosureIteratorConvertError extends IteratorException {
    /**
     * @param TItem $item
     */
    public function __construct(
        private mixed $item,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to convert item.', $previous);
    }

    /**
     * @return TItem
     */
    public function getItem(): mixed {
        return $this->item;
    }
}
