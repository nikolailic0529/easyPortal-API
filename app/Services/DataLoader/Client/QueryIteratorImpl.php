<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Client;

use App\GraphQL\Utils\Iterators\IteratorProperties;
use App\GraphQL\Utils\Iterators\QueryIterator;
use App\Services\DataLoader\Client\Exceptions\GraphQLRequestFailed;
use App\Services\DataLoader\Exceptions\FailedToProcessChunkItem;
use Closure;
use EmptyIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Iterator;
use Throwable;

use function array_filter;
use function array_map;
use function array_merge;
use function min;

abstract class QueryIteratorImpl implements QueryIterator {
    use IteratorProperties;

    /**
     * @param array<mixed> $params
     */
    public function __construct(
        protected ExceptionHandler $handler,
        protected Client $client,
        protected string $selector,
        protected string $graphql,
        protected array $params = [],
        protected ?Closure $retriever = null,
    ) {
        // empty
    }

    public function getIterator(): Iterator {
        // Prepare
        $index = 0;
        $chunk = $this->limit ? min($this->limit, $this->chunk) : $this->chunk;
        $limit = $this->limit;

        // Limit?
        if ($limit === 0) {
            return new EmptyIterator();
        }

        // Iterate
        do {
            $chunk  = $limit ? min($chunk, $limit - $index) : $chunk;
            $params = array_merge($this->params, $this->getQueryParams(), [
                'limit' => $chunk,
            ]);
            $items  = (array) $this->client->call($this->selector, $this->graphql, $params);
            $items  = $this->chunkPrepare($items);

            $this->chunkLoaded($items);

            foreach ($items as $item) {
                yield $index++ => $item;
            }

            if (!$this->chunkProcessed($items) || ($limit && $index >= $limit)) {
                break;
            }
        } while ($items);
    }

    /**
     * @return array<string,mixed>
     */
    abstract protected function getQueryParams(): array;

    /**
     * @param array<mixed> $items
     *
     * @return array<mixed>
     */
    protected function chunkPrepare(array $items): array {
        return $this->retriever ? array_filter(array_map(function (mixed $item): mixed {
            try {
                return ($this->retriever)($item);
            } catch (GraphQLRequestFailed $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                $this->handler->report(new FailedToProcessChunkItem($item, $exception));
            }

            return null;
        }, $items)) : $items;
    }
}
