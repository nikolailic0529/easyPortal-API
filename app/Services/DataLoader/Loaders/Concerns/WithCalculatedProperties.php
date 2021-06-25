<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

trait WithCalculatedProperties {
    use CalculatedProperties;

    private bool $recalculate = true;

    public function isRecalculate(): bool {
        return $this->recalculate;
    }

    public function setRecalculate(bool $recalculate): static {
        $this->recalculate = $recalculate;

        return $this;
    }

    public function recalculate(bool $force = false): void {
        if ($force || $this->isRecalculate()) {
            $this->updateCalculatedProperties(...$this->getResolversToRecalculate());
        }
    }

    /**
     * @return array<\App\Services\DataLoader\Resolver>
     */
    abstract protected function getResolversToRecalculate(): array;
}