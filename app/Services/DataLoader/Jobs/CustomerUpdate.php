<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateCustomer;
use App\Services\DataLoader\Jobs\Concerns\CommandOptions;
use Illuminate\Contracts\Console\Kernel;

/**
 * Updates Customer.
 *
 * @see \App\Services\DataLoader\Commands\UpdateCustomer
 */
class CustomerUpdate extends Sync {
    use CommandOptions;

    protected string $customerId;
    protected ?bool  $assets;
    protected ?bool  $documents;

    public function getCustomerId(): string {
        return $this->customerId;
    }

    public function getAssets(): ?bool {
        return $this->assets;
    }

    public function getDocuments(): ?bool {
        return $this->documents;
    }

    public function displayName(): string {
        return 'ep-data-loader-customer-update';
    }

    public function uniqueId(): string {
        return $this->customerId;
    }

    public function init(string $customerId, bool $assets = null, bool $documents = null): static {
        $this->customerId = $customerId;
        $this->assets     = $assets;
        $this->documents  = $documents;

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $kernel->call(UpdateCustomer::class, $this->setBooleanOptions(
            [
                'id' => $this->getCustomerId(),
            ],
            [
                'assets'           => $this->getAssets(),
                'assets-documents' => $this->getDocuments(),
            ],
        ));
    }
}
