<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

trait WithAssets {
    protected bool $withAssets = false;

    public function isWithAssets(): bool {
        return $this->withAssets;
    }

    public function setWithAssets(bool $withAssets): static {
        $this->withAssets = $withAssets;

        return $this;
    }
}
