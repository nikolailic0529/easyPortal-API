<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ErrorableSubjects;
use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\PropertiesProxy;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\Errorable;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Exceptions\ObjectIteratorIteratorError;
use Iterator;
use Throwable;

use function count;

/**
 * @template TItem
 * @template TValue
 *
 * @implements ObjectIterator<TItem>
 */
abstract class ObjectIteratorIterator implements ObjectIterator, Errorable {
    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\PropertiesProxy<TValue>
     */
    use PropertiesProxy;

    /**
     * @phpstan-use \App\Utils\Iterators\Concerns\InitialState<TItem>
     */
    use InitialState;

    /**
     * @phpstan-use Subjects<TItem>
     */
    use Subjects {
        Subjects::__clone as __cloneSubjects;
    }

    /**
     * @phpstan-use ErrorableSubjects<ObjectIteratorIteratorError<TValue>>
     */
    use ErrorableSubjects {
        ErrorableSubjects::__clone as __cloneErrorableSubjects;
        ErrorableSubjects::error as private;
    }

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
                $chunk = $this->chunkConvert($chunk);

                $this->chunkLoaded($chunk);

                yield from $chunk;

                $this->chunkProcessed($chunk);
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
    }
    //</editor-fold>

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
