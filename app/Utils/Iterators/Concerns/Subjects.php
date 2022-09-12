<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;

/**
 * @template TItem
 *
 * @mixin ObjectIterator<TItem>
 */
trait Subjects {
    /**
     * @var Dispatcher<void>
     */
    private Dispatcher $onInitDispatcher;

    /**
     * @var Dispatcher<void>
     */
    private Dispatcher $onFinishDispatcher;

    /**
     * @var Dispatcher<array<TItem>>
     */
    private Dispatcher $onBeforeChunkDispatcher;

    /**
     * @var Dispatcher<array<TItem>>
     */
    private Dispatcher $onAfterChunkDispatcher;

    public function __clone(): void {
        if (isset($this->onInitDispatcher)) {
            $this->onInitDispatcher = clone $this->onInitDispatcher;
        }

        if (isset($this->onFinishDispatcher)) {
            $this->onFinishDispatcher = clone $this->onFinishDispatcher;
        }

        if (isset($this->onBeforeChunkDispatcher)) {
            $this->onBeforeChunkDispatcher = clone $this->onBeforeChunkDispatcher;
        }

        if (isset($this->onAfterChunkDispatcher)) {
            $this->onAfterChunkDispatcher = clone $this->onAfterChunkDispatcher;
        }
    }

    public function onInit(?Closure $closure): static {
        if ($closure) {
            $this->getOnInitDispatcher()->attach($closure);
        } else {
            $this->getOnInitDispatcher()->reset();
        }

        return $this;
    }

    protected function initialized(): void {
        $this->getOnInitDispatcher()->notify();
    }

    /**
     * @return Dispatcher<void>
     */
    private function getOnInitDispatcher(): Dispatcher {
        if (!isset($this->onInitDispatcher)) {
            $this->onInitDispatcher = new Dispatcher();
        }

        return $this->onInitDispatcher;
    }

    public function onFinish(?Closure $closure): static {
        if ($closure) {
            $this->getOnFinishDispatcher()->attach($closure);
        } else {
            $this->getOnFinishDispatcher()->reset();
        }

        return $this;
    }

    protected function finished(): void {
        $this->getOnFinishDispatcher()->notify();
    }

    /**
     * @return Dispatcher<void>
     */
    private function getOnFinishDispatcher(): Dispatcher {
        if (!isset($this->onFinishDispatcher)) {
            $this->onFinishDispatcher = new Dispatcher();
        }

        return $this->onFinishDispatcher;
    }

    /**
     * Sets the closure that will be called after received each non-empty chunk.
     *
     * @param Closure(array<TItem>): void|null $closure `null` removes all observers
     */
    public function onBeforeChunk(?Closure $closure): static {
        if ($closure) {
            $this->getOnBeforeChunkDispatcher()->attach($closure);
        } else {
            $this->getOnBeforeChunkDispatcher()->reset();
        }

        return $this;
    }

    /**
     * @param array<TItem> $items
     */
    protected function chunkLoaded(array $items): void {
        if ($items) {
            $this->getOnBeforeChunkDispatcher()->notify($items);
        }
    }

    /**
     * @return Dispatcher<array<TItem>>
     */
    private function getOnBeforeChunkDispatcher(): Dispatcher {
        if (!isset($this->onBeforeChunkDispatcher)) {
            $this->onBeforeChunkDispatcher = new Dispatcher();
        }

        return $this->onBeforeChunkDispatcher;
    }

    /**
     * Sets the closure that will be called after non-empty chunk processed.
     *
     * @param Closure(array<TItem>): void|null $closure `null` removes all observers
     */
    public function onAfterChunk(?Closure $closure): static {
        if ($closure) {
            $this->getOnAfterChunkDispatcher()->attach($closure);
        } else {
            $this->getOnAfterChunkDispatcher()->reset();
        }

        return $this;
    }

    /**
     * @param array<TItem> $items
     */
    protected function chunkProcessed(array $items): bool {
        if ($items) {
            $this->getOnAfterChunkDispatcher()->notify($items);
        }

        return true;
    }

    /**
     * @return Dispatcher<array<TItem>>
     */
    private function getOnAfterChunkDispatcher(): Dispatcher {
        if (!isset($this->onAfterChunkDispatcher)) {
            $this->onAfterChunkDispatcher = new Dispatcher();
        }

        return $this->onAfterChunkDispatcher;
    }
}
