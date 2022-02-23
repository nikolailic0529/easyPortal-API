<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Services\DataLoader\Client\Client;

trait WithWarrantyCheck {
    private bool $withWarrantyCheck = false;

    public function isWithWarrantyCheck(): bool {
        return $this->withWarrantyCheck;
    }

    public function setWithWarrantyCheck(bool $withWarrantyCheck): static {
        $this->withWarrantyCheck = $withWarrantyCheck;

        return $this;
    }

    protected function runCustomerWarrantyCheck(string $id): bool {
        return $this->getClient()->runCustomerWarrantyCheck($id);
    }

    protected function runAssetWarrantyCheck(string $id): bool {
        return $this->getClient()->runAssetWarrantyCheck($id);
    }

    abstract protected function getClient(): Client;
}
