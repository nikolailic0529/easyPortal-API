<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Exceptions\FailedToProcessChunkItem;
use App\Utils\Iterators\ClosureIteratorIterator;
use Closure;
use Throwable;

/**
 * @template TItem
 * @template TValue of array<mixed>
 *
 * @extends ClosureIteratorIterator<TItem,TValue>
 */
class QueryIterator extends ClosureIteratorIterator {
    /**
     * @param class-string<OffsetBasedIterator<TValue>|LastIdBasedIterator<TValue>> $type
     * @param Query<TValue>                                                         $query
     * @param Closure(TValue): TItem                                                $converter
     */
    public function __construct(string $type, Query $query, Closure $converter) {
        $executor  = Closure::fromCallable($query);
        $iterator  = new $type($executor);
        $converter = static function (mixed $item) use ($query, $converter): mixed {
            /** @var TValue $item */
            try {
                return $converter($item);
            } catch (Throwable $exception) {
                throw new FailedToProcessChunkItem($query, $item, $exception);
            }
        };

        parent::__construct($iterator, $converter);
    }
}
