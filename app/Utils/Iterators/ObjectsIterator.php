<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;

/**
 * @template T
 * @template V
 *
 * @extends OneChunkOffsetBasedObjectIterator<T, V>
 */
class ObjectsIterator extends OneChunkOffsetBasedObjectIterator {
    /**
     * @param Collection<array-key, T>|array<T> $items
     * @param Closure(T):V|null                 $converter
     */
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Collection|array $items,
        ?Closure $converter = null,
    ) {
        parent::__construct(
            $exceptionHandler,
            static function () use ($items): array {
                return $items;
            },
            $converter,
        );
    }
}
