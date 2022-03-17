<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Utils\Iterators\OffsetBasedObjectIterator;

/**
 * @template T
 * @template V
 *
 * @implements OffsetBasedObjectIterator<T, V>
 */
class OffsetBasedIterator extends OffsetBasedObjectIterator {
    /**
     * @phpstan-use \App\Services\DataLoader\Client\IteratorErrorHandler<T, V>
     */
    use IteratorErrorHandler;
}
