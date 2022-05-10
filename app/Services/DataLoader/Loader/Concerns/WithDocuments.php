<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

trait WithDocuments {
    protected bool $withDocuments = false;

    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }
}
