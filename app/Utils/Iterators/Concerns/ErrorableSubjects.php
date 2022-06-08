<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use App\Utils\Iterators\Contracts\ObjectIterator;
use Closure;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use Throwable;


/**
 * @template TItem
 *
 * @mixin ObjectIterator<TItem>
 */
trait ErrorableSubjects {
    /**
     * @var Dispatcher<Throwable>
     */
    private Dispatcher $onErrorDispatcher;

    public function __clone(): void {
        if (isset($this->onErrorDispatcher)) {
            $this->onErrorDispatcher = clone $this->onErrorDispatcher;
        }
    }

    /**
     * @param Closure(Throwable): void|null $closure `null` removes all observers
     */
    public function onError(?Closure $closure): static {
        if ($closure) {
            $this->getOnErrorDispatcher()->attach($closure);
        } else {
            $this->getOnErrorDispatcher()->reset();
        }

        return $this;
    }

    /**
     * @return Dispatcher<Throwable>
     */
    private function getOnErrorDispatcher(): Dispatcher {
        if (!isset($this->onErrorDispatcher)) {
            $this->onErrorDispatcher = new Dispatcher();
        }

        return $this->onErrorDispatcher;
    }
}
