<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Exceptions\FailedToProcessChunkItem;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * @template T
 * @template V
 *
 * @extends \App\Utils\Iterators\ObjectIteratorImpl<T,V>
 */
trait IteratorErrorHandler {
    /**
     * @param \App\Services\DataLoader\Client\Query<V> $query
     * @param \Closure(mixed $item): T                 $retriever
     */
    public function __construct(
        protected ExceptionHandler $handler,
        protected Query $query,
        ?Closure $retriever = null,
    ) {
        parent::__construct(Closure::fromCallable($this->query), $retriever);
    }

    /**
     * @param \Closure(mixed $item): T $retriever
     *
     * @return T
     */
    protected function chunkPrepareItem(Closure $retriever, mixed $item): mixed {
        try {
            return parent::chunkPrepareItem($retriever, $item);
        } catch (GraphQLRequestFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handler->report(new FailedToProcessChunkItem($this->query, $item, $exception));
        }

        return null;
    }
}
