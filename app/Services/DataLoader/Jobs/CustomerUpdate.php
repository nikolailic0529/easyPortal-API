<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateCustomer;
use App\Services\DataLoader\Jobs\Concerns\CommandOptions;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

/**
 * Updates Customer.
 *
 * @see \App\Services\DataLoader\Commands\UpdateCustomer
 */
class CustomerUpdate extends Job implements ShouldBeUnique, Initializable {
    use CommandOptions;

    protected string $customerId;
    protected ?bool  $withAssets;
    protected ?bool  $withAssetsDocuments;

    public function displayName(): string {
        return 'ep-data-loader-customer-update';
    }

    public function uniqueId(): string {
        return $this->customerId;
    }

    public function init(string $customerId, bool $withAssets = null, bool $withAssetsDocuments = null): static {
        $this->customerId          = $customerId;
        $this->withAssets          = $withAssets;
        $this->withAssetsDocuments = $withAssetsDocuments;

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $kernel->call(UpdateCustomer::class, $this->setBooleanOptions(
            [
                'id' => $this->customerId,
            ],
            [
                'assets'           => $this->withAssets,
                'assets-documents' => $this->withAssetsDocuments,
            ],
        ));
    }
}
