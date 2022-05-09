<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

trait WithWarrantyCheck {
    private bool $withWarrantyCheck = false;

    public function isWithWarrantyCheck(): bool {
        return $this->withWarrantyCheck;
    }

    public function setWithWarrantyCheck(bool $withWarrantyCheck): static {
        $this->withWarrantyCheck = $withWarrantyCheck;

        return $this;
    }
}
