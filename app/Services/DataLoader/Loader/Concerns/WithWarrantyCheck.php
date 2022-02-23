<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\AssetWarrantyCheckFailed;
use App\Services\DataLoader\Exceptions\CustomerWarrantyCheckFailed;

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
        if (!$this->getClient()->runCustomerWarrantyCheck($id)) {
            throw new CustomerWarrantyCheckFailed($id);
        }

        return true;
    }

    protected function runAssetWarrantyCheck(string $id): bool {
        if (!$this->getClient()->runAssetWarrantyCheck($id)) {
            throw new AssetWarrantyCheckFailed($id);
        }

        return true;
    }

    abstract protected function getClient(): Client;
}
