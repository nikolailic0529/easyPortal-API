<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Exceptions\FailedToProcessChunkItem;
use App\Utils\Iterators\ObjectIteratorImpl;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * @template T
 * @template V
 *
 * @extends ObjectIteratorImpl<T,V>
 */
trait IteratorErrorHandler {
    /**
     * @param Query<V>          $query
     * @param Closure(mixed): T $converter
     */
    public function __construct(
        ExceptionHandler $handler,
        protected Query $query,
        ?Closure $converter = null,
    ) {
        parent::__construct($handler, Closure::fromCallable($this->query), $converter);
    }

    /**
     * @param V $item
     */
    protected function report(Throwable $exception, mixed $item): void {
        parent::report(new FailedToProcessChunkItem($this->query, $item, $exception), $item);
    }
}
