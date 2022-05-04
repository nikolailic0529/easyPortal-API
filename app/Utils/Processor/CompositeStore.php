<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Processor\Contracts\StateStore;

class CompositeStore implements StateStore {
    public function __construct(
        protected CompositeState $state,
    ) {
        // empty
    }

    /**
     * @inheritDoc
     */
    public function get(): ?array {
        return $this->getState()->state?->toArray();
    }

    public function save(State $state): State {
        $this->getState()->state = $state;

        return $state;
    }

    public function delete(): bool {
        // We do not reset state here because information can be useful.

        return true;
    }

    public function getState(): CompositeOperationState {
        $index                             = $this->state->index;
        $this->state->operations[$index] ??= new CompositeOperationState();

        return $this->state->operations[$index];
    }
}
