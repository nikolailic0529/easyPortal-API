<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateCustomer;
use Illuminate\Contracts\Console\Kernel;

/**
 * Syncs Customer.
 *
 * @see \App\Services\DataLoader\Commands\UpdateCustomer
 */
class CustomerSync extends Sync {
    public function displayName(): string {
        return 'ep-data-loader-customer-sync';
    }

    public function init(
        string $id,
        bool $warrantyCheck = null,
        bool $assets = null,
        bool $assetsDocuments = null,
    ): static {
        $this->objectId  = $id;
        $this->arguments = [
            'assets'           => $assets,
            'assets-documents' => $assetsDocuments,
            'warranty-check'   => $warrantyCheck,
        ];

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $this->checkCommandResult(
            $kernel->call(UpdateCustomer::class, $this->setBooleanOptions(
                [
                    'id' => $this->objectId,
                ],
                $this->getArguments(),
            )),
        );
    }
}
