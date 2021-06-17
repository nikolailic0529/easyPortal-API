<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

/**
 * Update customer.
 */
class CustomerUpdate extends Job implements ShouldBeUnique, NamedJob, Initializable {
    /**
     * Customer Id that should be updated.
     */
    protected string $customerId;

    public function displayName(): string {
        return 'ep-data-loader-customer-update';
    }

    public function uniqueId(): string {
        return $this->getCustomerId();
    }

    public function getCustomerId(): string {
        return $this->customerId;
    }

    public function initialize(string $id): self {
        $this->customerId = $id;

        return $this->initialized();
    }

    public function handle(Kernel $artisan): void {
        $artisan->call('ep:data-loader-update-customer', [
            'id'       => [$this->getCustomerId()],
            '--assets' => true,
        ]);
    }
}
