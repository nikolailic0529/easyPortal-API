<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Concerns;

trait WithForce {
    private bool $force = false;

    public function isForce(): bool {
        return $this->force;
    }

    public function setForce(bool $force): static {
        $this->force = $force;

        return $this;
    }
}
