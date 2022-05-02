<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Concerns;

trait WithCustomer {
    private string $customerId;

    public function getCustomerId(): string {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): static {
        $this->customerId = $customerId;

        return $this;
    }
}
