<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateAsset;
use App\Services\DataLoader\Jobs\Concerns\CommandOptions;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

/**
 * Updates Asset.
 *
 * @see \App\Services\DataLoader\Commands\UpdateAsset
 */
class AssetUpdate extends Job implements ShouldBeUnique, Initializable {
    use CommandOptions;

    protected string $assetId;
    protected ?bool  $withDocuments;

    public function getAssetId(): string {
        return $this->assetId;
    }

    public function getWithDocuments(): ?bool {
        return $this->withDocuments;
    }

    public function displayName(): string {
        return 'ep-data-loader-asset-update';
    }

    public function uniqueId(): string {
        return $this->getAssetId();
    }

    public function init(string $assetId, bool $withDocuments = null): static {
        $this->assetId       = $assetId;
        $this->withDocuments = $withDocuments;

        $this->initialized();

        return $this;
    }

    public function __invoke(Kernel $kernel): void {
        $kernel->call(UpdateAsset::class, $this->setBooleanOptions(
            [
                'id' => $this->getAssetId(),
            ],
            [
                'documents' => $this->getWithDocuments(),
            ],
        ));
    }
}
