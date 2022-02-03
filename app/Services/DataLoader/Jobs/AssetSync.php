<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateAsset;
use Illuminate\Contracts\Console\Kernel;

use function array_merge;

/**
 * Syncs Asset.
 *
 * @see \App\Services\DataLoader\Commands\UpdateAsset
 */
class AssetSync extends Sync {
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
        $this->checkCommandResult(
            $kernel->call(UpdateAsset::class, $this->getOptions(array_merge(
                $this->getArguments(),
                [
                    'id' => $this->objectId,
                ],
            ))),
        );
    }
}
