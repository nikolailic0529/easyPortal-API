<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use App\Utils\Iterators\Contracts\ObjectIterator;

/**
 * @template TItem
 */
trait PropertiesProxy {
    /**
     * @return ObjectIterator<TItem>
     */
    abstract protected function getInternalIterator(): ObjectIterator;

    public function getIndex(): int {
        return $this->getInternalIterator()->getIndex();
    }

    public function setIndex(int $index): static {
        $this->getInternalIterator()->setIndex($index);

        return $this;
    }

    public function getLimit(): ?int {
        return $this->getInternalIterator()->getLimit();
    }

    public function setLimit(?int $limit): static {
        $this->getInternalIterator()->setLimit($limit);

        return $this;
    }

    public function getChunkSize(): int {
        return $this->getInternalIterator()->getChunkSize();
    }

    public function setChunkSize(?int $chunk): static {
        $this->getInternalIterator()->setChunkSize($chunk);

        return $this;
    }

    public function getOffset(): string|int|null {
        return $this->getInternalIterator()->getOffset();
    }

    public function setOffset(int|string|null $offset): static {
        $this->getInternalIterator()->setOffset($offset);

        return $this;
    }
}
