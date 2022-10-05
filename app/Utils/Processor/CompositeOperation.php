<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Processor\Contracts\MixedProcessor;
use Closure;

/**
 * @template TState of \App\Utils\Processor\CompositeState
 */
class CompositeOperation {
    /**
     * @param Closure(TState): MixedProcessor  $factory
     * @param Closure(TState, bool): void|null $handler
     */
    public function __construct(
        protected string $name,
        protected Closure $factory,
        protected ?Closure $handler = null,
    ) {
        // empty
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @param TState $state
     */
    public function getProcessor(CompositeState $state): MixedProcessor {
        return ($this->factory)($state);
    }

    /**
     * @return Closure(TState, bool): void|null
     */
    public function getHandler(): ?Closure {
        return $this->handler;
    }
}
