<?php declare(strict_types = 1);

namespace App\Utils\Processor;

class CompositeState extends State {
    /**
     * @var array<int, array<string, mixed>|null>
     */
    public array $operations;

    private ?string $currentOperationName = null;
    private ?State $currentOperationState = null;

    public function resetCurrentOperation(): static {
        $this->setCurrentOperationName(null);
        $this->setCurrentOperationState(null);

        return $this;
    }

    public function getCurrentOperationName(): ?string {
        return $this->currentOperationName;
    }

    public function setCurrentOperationName(?string $name): static {
        $this->currentOperationName = $name;

        return $this;
    }

    public function getCurrentOperationState(): ?State {
        return $this->currentOperationState;
    }

    public function setCurrentOperationState(?State $state): static {
        $this->currentOperationState = $state;

        return $this;
    }
}
