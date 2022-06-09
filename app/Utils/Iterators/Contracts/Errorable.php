<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Contracts;

use Closure;
use Throwable;

interface Errorable {
    /**
     * Sets the closure that will be called if error.
     *
     * @param Closure(Throwable): void|null $closure `null` removes all observers
     */
    public function onError(?Closure $closure): static;
}
