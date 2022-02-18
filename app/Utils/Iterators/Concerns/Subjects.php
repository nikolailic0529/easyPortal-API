<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use Closure;
use LastDragon_ru\LaraASP\Core\Observer\Subject;

/**
 * @template T
 *
 * @mixin \App\Utils\Iterators\Contracts\ObjectIterator<T>
 */
trait Subjects {
    private Subject $onInitSubject;
    private Subject $onFinishSubject;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Subject<array<T>>
     */
    private Subject $onBeforeChunkSubject;

    /**
     * @var \LastDragon_ru\LaraASP\Core\Observer\Subject<array<T>>
     */
    private Subject $onAfterChunkSubject;

    public function __clone(): void {
        if (isset($this->onInitSubject)) {
            $this->onInitSubject = clone $this->onInitSubject;
        }

        if (isset($this->onFinishSubject)) {
            $this->onFinishSubject = clone $this->onFinishSubject;
        }

        if (isset($this->onBeforeChunkSubject)) {
            $this->onBeforeChunkSubject = clone $this->onBeforeChunkSubject;
        }

        if (isset($this->onAfterChunkSubject)) {
            $this->onAfterChunkSubject = clone $this->onAfterChunkSubject;
        }
    }

    public function onInit(?Closure $closure): static {
        if ($closure) {
            $this->getOnInitSubject()->attach($closure);
        } else {
            $this->getOnInitSubject()->reset();
        }

        return $this;
    }

    protected function initialized(): void {
        $this->getOnInitSubject()->notify();
    }

    private function getOnInitSubject(): Subject {
        if (!isset($this->onInitSubject)) {
            $this->onInitSubject = new Subject();
        }

        return $this->onInitSubject;
    }

    public function onFinish(?Closure $closure): static {
        if ($closure) {
            $this->getOnFinishSubject()->attach($closure);
        } else {
            $this->getOnFinishSubject()->reset();
        }

        return $this;
    }

    protected function finished(): void {
        $this->getOnFinishSubject()->notify();
    }

    private function getOnFinishSubject(): Subject {
        if (!isset($this->onFinishSubject)) {
            $this->onFinishSubject = new Subject();
        }

        return $this->onFinishSubject;
    }

    /**
     * Sets the closure that will be called after received each non-empty chunk.
     *
     * @param \Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onBeforeChunk(?Closure $closure): static {
        if ($closure) {
            $this->getOnBeforeChunkSubject()->attach($closure);
        } else {
            $this->getOnBeforeChunkSubject()->reset();
        }

        return $this;
    }

    /**
     * @param array<T> $items
     */
    protected function chunkLoaded(array $items): void {
        if ($items) {
            $this->getOnBeforeChunkSubject()->notify($items);
        }
    }

    /**
     * @return \LastDragon_ru\LaraASP\Core\Observer\Subject<array<T>>
     */
    private function getOnBeforeChunkSubject(): Subject {
        if (!isset($this->onBeforeChunkSubject)) {
            $this->onBeforeChunkSubject = new Subject();
        }

        return $this->onBeforeChunkSubject;
    }

    /**
     * Sets the closure that will be called after non-empty chunk processed.
     *
     * @param \Closure(array<T>): void|null $closure `null` removes all observers
     *
     * @return $this<T>
     */
    public function onAfterChunk(?Closure $closure): static {
        if ($closure) {
            $this->getOnAfterChunkSubject()->attach($closure);
        } else {
            $this->getOnAfterChunkSubject()->reset();
        }

        return $this;
    }

    /**
     * @param array<T> $items
     */
    protected function chunkProcessed(array $items): bool {
        if ($items) {
            $this->getOnAfterChunkSubject()->notify($items);
        }

        return true;
    }

    /**
     * @return \LastDragon_ru\LaraASP\Core\Observer\Subject<array<T>>
     */
    private function getOnAfterChunkSubject(): Subject {
        if (!isset($this->onAfterChunkSubject)) {
            $this->onAfterChunkSubject = new Subject();
        }

        return $this->onAfterChunkSubject;
    }
}
