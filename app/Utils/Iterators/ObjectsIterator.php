<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Illuminate\Support\Collection;

/**
 * @template T
 * @template V
 *
 * @implements \App\Utils\Iterators\OneChunkOffsetBasedObjectIterator<T, V>
 */
class ObjectsIterator extends OneChunkOffsetBasedObjectIterator {
    /**
     * @param \Illuminate\Support\Collection<T>|array<T> $items
     * @param \Closure(T):V|null                         $converter
     */
    public function __construct(Collection|array $items, ?Closure $converter = null) {
        parent::__construct(
            static function () use ($items): array {
                return $items;
            },
            $converter,
        );
    }
}
