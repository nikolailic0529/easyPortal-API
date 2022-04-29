<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Concerns;

trait WithReseller {
    private string $resellerId;

    public function getResellerId(): string {
        return $this->resellerId;
    }

    public function setResellerId(string $resellerId): static {
        $this->resellerId = $resellerId;

        return $this;
    }
}
