<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Eloquent;

use App\Utils\Iterators\Concerns\ChunkSize;
use App\Utils\Iterators\Concerns\InitialState;
use App\Utils\Iterators\Concerns\Subjects;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use Generator;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator as LaraASPIterator;
use LastDragon_ru\LaraASP\Eloquent\Iterators\IteratorImpl;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements ObjectIterator<TModel>
 */
class EloquentIterator implements ObjectIterator, MixedIterator {
    /**
     * @phpstan-use Subjects<TModel>
     */
    use Subjects {
        __clone as __cloneSubjects;
    }

    use ChunkSize;

    /**
     * @phpstan-use InitialState<TModel>
     */
    use InitialState;

    /**
     * @param LaraASPIterator<TModel> $iterator
     */
    public function __construct(
        protected LaraASPIterator $iterator,
    ) {
        // empty
    }

    // <editor-fold desc="ObjectIterator">
    // =========================================================================
    public function getCount(): ?int {
        return $this->iterator instanceof IteratorImpl
            ? (new EloquentHelper())->getCount($this->iterator)
            : null;
    }

    public function getIterator(): Generator {
        try {
            $this->init();

            yield from $this->iterator->getIterator();
        } finally {
            $this->finish();
        }
    }

    public function getIndex(): int {
        return $this->iterator->getIndex();
    }

    public function setIndex(int $index): static {
        $this->iterator->setIndex($index);

        return $this;
    }

    public function getLimit(): ?int {
        return $this->iterator->getLimit();
    }

    public function setLimit(?int $limit): static {
        $this->iterator->setLimit($limit);

        return $this;
    }

    public function getChunkSize(): int {
        return $this->iterator->getChunkSize();
    }

    public function setChunkSize(?int $chunk): static {
        $this->iterator->setChunkSize($chunk ?? $this->getDefaultChunkSize());

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->iterator->getOffset();
    }

    public function setOffset(int|string|null $offset): static {
        $this->iterator->setOffset($offset);

        return $this;
    }

    public function onBeforeChunk(?Closure $closure): static {
        $this->iterator->onBeforeChunk($this->wrapClosure($closure));

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->iterator->onAfterChunk($this->wrapClosure($closure));

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    /**
     * @param Closure(array<int, TModel>) :void|null $closure
     *
     * @return Closure(Collection<int, TModel>):void
     */
    protected function wrapClosure(?Closure $closure): Closure {
        return static function (Collection $items) use ($closure): void {
            if ($closure) {
                $closure($items->all());
            }
        };
    }

    public function __clone(): void {
        $this->iterator = clone $this->iterator;

        $this->__cloneSubjects();
    }
    // </editor-fold>
}
