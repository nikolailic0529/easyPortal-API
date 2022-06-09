<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Utils\Iterators\ObjectIteratorImpl;

use function end;
use function is_array;
use function is_int;
use function is_string;

/**
 * @template T
 *
 * @extends ObjectIteratorImpl<T, array{limit: int, lastId: string|int|null}>
 */
class LastIdBasedIterator extends ObjectIteratorImpl {
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

        if (is_array($last) && isset($last['id']) && (is_string($last['id']) || is_int($last['id']))) {
            $this->setOffset($last['id']);

            $continue = true;
        }

        return parent::chunkProcessed($items)
            && $continue;
    }
}
