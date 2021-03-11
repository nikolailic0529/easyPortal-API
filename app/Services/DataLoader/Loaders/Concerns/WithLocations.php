<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

trait WithLocations {
    protected bool $withLocations = true;

    public function isWithLocations(): bool {
        return $this->withLocations;
    }

    public function setWithLocations(bool $withLocations): static {
        $this->withLocations = $withLocations;

        return $this;
    }
}
