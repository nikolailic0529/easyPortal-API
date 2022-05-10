<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Concerns;

trait WithObjectId {
    private string $objectId;

    public function getObjectId(): string {
        return $this->objectId;
    }

    public function setObjectId(string $objectId): static {
        $this->objectId = $objectId;

        return $this;
    }
}
