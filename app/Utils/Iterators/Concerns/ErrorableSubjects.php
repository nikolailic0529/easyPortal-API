<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

use Closure;
use LastDragon_ru\LaraASP\Core\Observer\Dispatcher;
use Throwable;


/**
 * @template TError of Throwable
 */
trait ErrorableSubjects {
    /**
     * @var Dispatcher<TError>
     */
    private Dispatcher $onErrorDispatcher;

    public function __clone(): void {
        if (isset($this->onErrorDispatcher)) {
            $this->onErrorDispatcher = clone $this->onErrorDispatcher;
        }
    }

    /**
     * @param Closure(TError): void|null $closure `null` removes all observers
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
     * @return Dispatcher<TError>
     */
    private function getOnErrorDispatcher(): Dispatcher {
        if (!isset($this->onErrorDispatcher)) {
            $this->onErrorDispatcher = new Dispatcher();
        }

        return $this->onErrorDispatcher;
    }

    /**
     * @param TError $error
     */
    protected function error(Throwable $error): void {
        $this->getOnErrorDispatcher()->notify($error);
    }
}
