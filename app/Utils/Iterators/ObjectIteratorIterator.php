<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ErrorableSubjects;
use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\PropertiesProxy;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\Errorable;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Exceptions\ObjectIteratorIteratorError;
use Closure;
use Iterator;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use Throwable;

use function count;

/**
 * @template TItem
 * @template TValue
 *
 * @implements ObjectIterator<TItem>
 */
abstract class ObjectIteratorIterator implements ObjectIterator, MixedIterator, Errorable {
    /**
     * @use PropertiesProxy<TValue>
     */
    use PropertiesProxy;

    /**
     * @use InitialState<TItem>
     */
    use InitialState;

    /**
     * @use Subjects<TItem>
     */
    use Subjects {
        Subjects::__clone as __cloneSubjects;
    }

    /**
     * @use ErrorableSubjects<ObjectIteratorIteratorError<TValue>>
     */
    use ErrorableSubjects {
        ErrorableSubjects::__clone as __cloneErrorableSubjects;
        ErrorableSubjects::error as private;
    }

    /**
     * @var Dispatcher<array<TValue>>
     */
    private Dispatcher $onPrepareChunkDispatcher;

    /**
     * @param ObjectIterator<TValue> $internalIterator
     */
    public function __construct(
        protected ObjectIterator $internalIterator,
    ) {
        // empty
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function getCount(): ?int {
        return $this->getInternalIterator()->getCount();
    }

    /**
     * @return ObjectIterator<TValue>
     */
    protected function getInternalIterator(): ObjectIterator {
        return $this->internalIterator;
    }
    // </editor-fold>

    // <editor-fold desc="IteratorAggregate">
    // =========================================================================
    /**
     * @return Iterator<TItem>
     */
    public function getIterator(): Iterator {
        try {
            $this->init();

            foreach ($this->getChunks() as $chunk) {
                $items = $this->chunkConvert($chunk);

                $this->chunkLoaded($items);
                $this->chunkPrepared($chunk, $items);

                yield from $items;

                $this->chunkProcessed($items);
            }
        } finally {
            $this->finish();
        }
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    /**
     * @return Iterator<array<TValue>>
     */
    protected function getChunks(): Iterator {
        // Split sequence into groups
        $index = 0;
        $items = [];
        $chunk = $this->getChunkSize();

        foreach ($this->getInternalIterator() as $key => $item) {
            // Combine items into group
            $items[$key] = $item;

            if (count($items) < $chunk) {
                continue;
            }

            // Process
            yield $index++ => $items;

            // Reset
            $items = [];
        }

        // Tail
        if ($items) {
            yield $index++ => $items;
        }
    }

    /**
     * @param TValue $item
     */
    protected function report(mixed $item, ?Throwable $error): void {
        $this->error(new ObjectIteratorIteratorError($item, $error));
    }

    public function __clone(): void {
        $this->__cloneSubjects();
        $this->__cloneErrorableSubjects();

        if (isset($this->onPrepareChunkDispatcher)) {
            $this->onPrepareChunkDispatcher = clone $this->onPrepareChunkDispatcher;
        }
    }
    //</editor-fold>

    // <editor-fold desc="Subjects">
    // =========================================================================
    /**
     * @param Closure(array<TValue>): void|null $closure `null` removes all observers
     */
    public function onPrepareChunk(?Closure $closure): static {
        if ($closure) {
            $this->getOnPrepareChunkDispatcher()->attach($closure);
        } else {
            $this->getOnPrepareChunkDispatcher()->reset();
        }

        return $this;
    }

    /**
     * @return Dispatcher<array<TValue>>
     */
    private function getOnPrepareChunkDispatcher(): Dispatcher {
        if (!isset($this->onPrepareChunkDispatcher)) {
            $this->onPrepareChunkDispatcher = new Dispatcher();
        }

        return $this->onPrepareChunkDispatcher;
    }

    /**
     * @template C of array<TValue>
     *
     * @param C            $chunk
     * @param array<TItem> $items
     *
     * @return C
     */
    protected function chunkPrepared(array $chunk, array $items): array {
        if ($chunk && $items) {
            $this->getOnPrepareChunkDispatcher()->notify($chunk);
        }

        return $chunk;
    }
    // </editor-fold>

    // <editor-fold desc="Abstract">
    // =========================================================================
    /**
     * @param array<TValue> $items
     *
     * @return array<TItem>
     */
    abstract protected function chunkConvert(array $items): array;
    //</editor-fold>
}
