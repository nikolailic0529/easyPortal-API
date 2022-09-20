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
        return $this->state->operations[$this->getKey()] ?? null;
    }

    public function save(State $state): State {
        $this->state->operations[$this->getKey()] = $state->toArray();

        return $state;
    }

    public function delete(): bool {
        // We do not reset state here because information can be useful.

        return true;
    }

    protected function getKey(): int {
        return $this->state->index;
    }
}
