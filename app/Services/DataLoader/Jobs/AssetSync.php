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

    protected string $assetId;
    protected ?bool  $documents;

    public function getAssetId(): string {
        return $this->assetId;
    }

    public function getDocuments(): ?bool {
        return $this->documents;
    }

    public function displayName(): string {
        return 'ep-data-loader-asset-sync';
    }

    public function uniqueId(): string {
        return $this->getAssetId();
    }

    public function init(string $assetId, bool $documents = null): static {
        $this->assetId   = $assetId;
        $this->documents = $documents;

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $kernel->call(UpdateAsset::class, $this->setBooleanOptions(
            [
                'id' => $this->getAssetId(),
            ],
            [
                'documents' => $this->getDocuments(),
            ],
        ));
    }
}
