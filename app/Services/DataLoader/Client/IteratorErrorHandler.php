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
     * @param \Closure(mixed $item): T                 $converter
     */
    public function __construct(
        protected ExceptionHandler $handler,
        protected Query $query,
        ?Closure $converter = null,
    ) {
        parent::__construct(Closure::fromCallable($this->query), $converter);
    }

    /**
     * @param T $item
     *
     * @return V
     */
    protected function chunkConvertItem(mixed $item): mixed {
        try {
            return parent::chunkConvertItem($item);
        } catch (GraphQLRequestFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->handler->report(new FailedToProcessChunkItem($this->query, $item, $exception));
        }

        return null;
    }
}
