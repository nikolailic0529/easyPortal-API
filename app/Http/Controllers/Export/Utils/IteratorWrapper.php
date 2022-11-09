<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Utils\Iterators\Contracts\ObjectIterator;
use Iterator;
use IteratorAggregate;

/**
 * The wrapper just add `null` to end of sequence (that needed only to avoid
 * code duplication in {@see \App\Http\Controllers\Export\ExportController::getRowsIterator()})
 *
 * @template TItem
 *
 * @implements IteratorAggregate<array-key, TItem>
 */
class IteratorWrapper implements IteratorAggregate {
    /**
     * @param ObjectIterator<TItem> $iterator
     */
    public function __construct(
        protected ObjectIterator $iterator,
    ) {
        // empty
    }

    /**
     * @return Iterator<array-key, TItem|null>
     */
    public function getIterator(): Iterator {
        yield from $this->iterator;
        yield;
    }
}
