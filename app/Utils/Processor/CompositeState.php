<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\JsonObject\JsonObjectArray;

class CompositeState extends State {
    /**
     * @var array<int, CompositeOperationState>
     */
    #[JsonObjectArray(CompositeOperationState::class)]
    public array $operations;

    public function getCurrentState(): ?CompositeOperationState {
        return $this->operations[$this->index] ?? null;
    }
}
