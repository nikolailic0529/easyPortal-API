<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Exceptions\FailedToProcessItem;
use App\Utils\Iterators\ObjectIteratorIterator;
use Throwable;

/**
 * @template T of \App\Utils\Eloquent\Model|string
 * @template V of \App\Services\DataLoader\Schema\Type
 *
 * @extends ObjectIteratorIterator<T,V>
 */
class IteratorIterator extends ObjectIteratorIterator {
    /**
     * @param V $item
     */
    protected function report(Throwable $exception, mixed $item): void {
        parent::report(new FailedToProcessItem($item, $exception), $item);
    }
}
