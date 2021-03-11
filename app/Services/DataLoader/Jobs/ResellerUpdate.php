<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

/**
 * Update reseller.
 */
class ResellerUpdate extends Job implements Initializable {
    /**
     * Reseller Id that should be updated.
     */
    protected string $resellerId;

    public function uniqueId(): string {
        return $this->getResellerId();
    }

    public function getResellerId(): string {
        return $this->resellerId;
    }

    public function initialize(string $id): self {
        $this->resellerId = $id;

        return $this->initialized();
    }

    public function handle(): void {
        // to do
    }
}
