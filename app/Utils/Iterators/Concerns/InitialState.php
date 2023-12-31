<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use App\Utils\Iterators\Contracts\ObjectIterator;

/**
 * @template TItem
 *
 * @mixin ObjectIterator<TItem>
 */
trait InitialState {
    /**
     * @var ObjectIterator<TItem>|null
     */
    private ?ObjectIterator $initial = null;

    protected function init(): void {
        $this->initial = clone $this;

        $this->initialized();
    }

    protected function finish(): void {
        $this->finished();

        if ($this->initial) {
            $this->setIndex($this->initial->getIndex());
            $this->setLimit($this->initial->getLimit());
            $this->setOffset($this->initial->getOffset());
        }

        $this->initial = null;
    }

    abstract protected function initialized(): void;

    abstract protected function finished(): void;
}
