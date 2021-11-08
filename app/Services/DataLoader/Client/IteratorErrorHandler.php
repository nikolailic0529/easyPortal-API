<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Exceptions\FailedToProcessChunkItem;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * @template T
 *
 * @extends \App\GraphQL\Utils\Iterators\QueryIteratorImpl
 */
trait IteratorErrorHandler {
    /**
     * @param \Closure(array $variables): array<mixed> $executor
     * @param \Closure(mixed $item): T                 $retriever
     */
    public function __construct(
        protected ExceptionHandler $handler,
        ?Closure $executor,
        ?Closure $retriever = null,
    ) {
        parent::__construct($executor, $retriever);
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
            $this->handler->report(new FailedToProcessChunkItem($item, $exception));
        }

        return null;
    }
}
