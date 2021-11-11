<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateAsset;
use App\Services\DataLoader\Jobs\Concerns\CommandOptions;
use Illuminate\Contracts\Console\Kernel;

/**
 * Syncs Asset.
 *
 * @see \App\Services\DataLoader\Commands\UpdateAsset
 */
class AssetSync extends Sync {
    use CommandOptions;

    public function displayName(): string {
        return 'ep-data-loader-asset-sync';
    }

    public function init(string $id, bool $warrantyCheck = null, bool $documents = null): static {
        $this->objectId  = $id;
        $this->arguments = [
            'warranty-check' => $warrantyCheck,
            'documents'      => $documents,
        ];

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $kernel->call(UpdateAsset::class, $this->setBooleanOptions(
            [
                'id' => $this->getObjectId(),
            ],
            $this->getArguments(),
        ));
    }
}
