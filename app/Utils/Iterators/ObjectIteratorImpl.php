<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ChunkConverter;
use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\Properties;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Exceptions\InfiniteLoopDetected;
use Closure;
use EmptyIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Iterator;

use function end;
use function min;
use function reset;

/**
 * @template T
 * @template V
 *
 * @implements ObjectIterator<T>
 */
abstract class ObjectIteratorImpl implements ObjectIterator {
    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\Subjects<T>
     */
    use Subjects;
    use Properties;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\InitialState<T>
     */
    use InitialState;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\ChunkConverter<T,V>
     */
    use ChunkConverter;

    /**
     * @var array{?V,?V}|null
     */
    private ?array $previous = null;

    /**
     * @param Closure(array<string,mixed>): array<V> $executor
     * @param Closure(V): T|null                     $converter
     */
    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected ?Closure $executor,
        protected ?Closure $converter = null,
    ) {
        // empty
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    /**
     * @return Closure(V): T|null
     */
    protected function getConverter(): ?Closure {
        return $this->converter;
    }

    /**
     * @return Iterator<T>
     */
    public function getIterator(): Iterator {
        // Prepare
        $index = $this->getIndex();
        $limit = $this->getLimit();
        $chunk = $limit ? min($limit, $this->getChunkSize()) : $this->getChunkSize();

        // Limit?
        if ($limit === 0) {
            return new EmptyIterator();
        }

        // Iterate
        try {
            $this->init();

            do {
                $chunk = $limit ? min($chunk, $limit - $index) : $chunk;
                $items = $this->getChunk($chunk);
                $items = $this->chunkPrepare($items);

                $this->chunkLoaded($items);

                foreach ($items as $item) {
                    yield $index++ => $item;

                    $this->setIndex($index);
                }

                if (!$this->chunkProcessed($items) || ($limit && $index >= $limit)) {
                    break;
                }
            } while ($items);
        } finally {
            $this->finish();
        }
    }

    /**
     * @param array<string,mixed> $variables
     *
     * @return array<V>
     */
    protected function execute(array $variables): array {
        return $this->executor
            ? ($this->executor)($variables)
            : [];
    }

    /**
     * @return array<V>
     */
    protected function getChunk(int $limit): array {
        return $this->execute($this->getChunkVariables($limit));
    }

    /**
     * @return array<string,mixed>
     */
    abstract protected function getChunkVariables(int $limit): array;

    /**
     * @param array<V> $items
     *
     * @return array<T>
     */
    protected function chunkPrepare(array $items): array {
        // Infinite loop?
        $current        = [$items ? end($items) : null, $items ? reset($items) : null];
        $previous       = $this->previous;
        $this->previous = $current;

        if ($previous && $current === $previous) {
            $this->getExceptionHandler()->report(
                new InfiniteLoopDetected($this::class),
            );

            $items = [];
        }

        // Convert
        return $this->chunkConvert($items);
    }
}
