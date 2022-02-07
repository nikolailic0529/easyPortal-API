<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ChunkSize;
use Closure;
use Exception;
use Generator;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator as LaraASPIterator;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 *
 * @implements \App\Utils\Iterators\ObjectIterator<T>
 */
class EloquentIterator implements ObjectIterator {
    /**
     * @param \LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator<T> $iterator
     */
    public function __construct(
        protected LaraASPIterator $iterator,
    ) {
        // empty
    }

    public function getIterator(): Generator {
        return $this->iterator->getIterator();
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
        $this->iterator->setIndex($limit ?? 0);

        return $this;
    }

    public function getChunkSize(): int {
        return $this->iterator->getChunkSize();
    }

    public function setChunkSize(?int $chunk): static {
        $this->iterator->setChunkSize($chunk ?? ChunkSize::getDefaultChunkSize());

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
        $this->iterator->onAfterChunk($this->wrapClosure($closure));

        return $this;
    }

    public function onAfterChunk(?Closure $closure): static {
        $this->iterator->onAfterChunk($this->wrapClosure($closure));

        return $this;
    }

    public function onInit(?Closure $closure): static {
        throw new Exception('Not implemented');
    }

    public function onFinish(?Closure $closure): static {
        throw new Exception('Not implemented');
    }

    /**
     * @param \Closure(array<T>):void|null $closure
     *
     * @return \Closure(\Illuminate\Support\Collection<T>):void|null
     */
    protected function wrapClosure(?Closure $closure): ?Closure {
        return static function (Collection $items) use ($closure): void {
            $closure($items->all());
        };
    }
}
