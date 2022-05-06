<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Processor\Contracts\Processor;
use Closure;

/**
 * @template TState of \App\Utils\Processor\CompositeState
 */
class CompositeOperation {
    /**
     * @param Closure(TState): Processor<mixed, mixed, State> $factory
     */
    public function __construct(
        protected string $name,
        protected Closure $factory,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @param TState $state
     *
     * @return Processor<mixed, mixed, State>
     */
    public function getProcessor(CompositeState $state): Processor {
        return ($this->factory)($state);
    }
}
