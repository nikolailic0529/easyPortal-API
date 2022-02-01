<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\AssetWarrantyCheckFailed;
use App\Services\DataLoader\Exceptions\CustomerWarrantyCheckFailed;
use App\Services\DataLoader\Schema\TriggerCoverageStatusCheck;

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
        $input  = new TriggerCoverageStatusCheck(['customerId' => $id]);
        $result = $this->getClient()->triggerCoverageStatusCheck($input);

        if (!$result) {
            throw new CustomerWarrantyCheckFailed($id);
        }

        return $result;
    }

    protected function runAssetWarrantyCheck(string $id): bool {
        $input  = new TriggerCoverageStatusCheck(['assetId' => $id]);
        $result = $this->getClient()->triggerCoverageStatusCheck($input);

        if (!$result) {
            throw new AssetWarrantyCheckFailed($id);
        }

        return $result;
    }

    abstract protected function getClient(): Client;
}
