<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Schema\TypeWithId;
use App\Utils\Iterators\ObjectIteratorImpl;
use App\Utils\Iterators\OffsetBasedObjectIterator;

use function end;

/**
 * @template T
 * @template V
 *
 * @implements  OffsetBasedObjectIterator<T, V>
 */
class LastIdBasedIterator extends ObjectIteratorImpl {
    /**
     * @phpstan-use \App\Services\DataLoader\Client\IteratorErrorHandler<T, V>
     */
    use IteratorErrorHandler;

    /**
     * @inheritDoc
     */
    protected function getChunkVariables(int $limit): array {
        return [
            'lastId' => $this->getOffset(),
            'limit'  => $limit,
        ];
    }

    /**
     * @param array<mixed> $items
     */
    protected function chunkProcessed(array $items): bool {
        $last     = end($items);
        $continue = false;

        if ($last instanceof TypeWithId && isset($last->id)) {
            $this->setOffset($last->id);

            $continue = true;
        }

        return parent::chunkProcessed($items)
            && $continue;
    }
}
