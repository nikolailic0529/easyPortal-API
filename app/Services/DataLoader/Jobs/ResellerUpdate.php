<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

/**
 * Update reseller.
 */
class ResellerUpdate extends Job implements ShouldBeUnique, NamedJob, Initializable {
    /**
     * Reseller Id that should be updated.
     */
    protected string $resellerId;

    public function displayName(): string {
        return 'ep-data-loader-reseller-update';
    }

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

    public function handle(Kernel $artisan): void {
        $artisan->call('ep:data-loader-update-reseller', [
            'id'       => [$this->getResellerId()],
            '--assets' => true,
        ]);
    }
}
