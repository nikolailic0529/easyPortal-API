<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

/**
 * @mixin \App\Utils\Iterators\ObjectIterator
 */
trait ObjectIteratorInitialState {
    private ?ObjectIterator $initial = null;

    protected function init(): void {
        $this->initial = clone $this;

        $this->initialized();
    }

    protected function finish(): void {
        if ($this->initial) {
            $this->setIndex($this->initial->getIndex());
            $this->setLimit($this->initial->getLimit());
            $this->setOffset($this->initial->getOffset());
        }

        $this->initial = null;

        $this->finished();
    }

    abstract protected function initialized(): void;

    abstract protected function finished(): void;
}
