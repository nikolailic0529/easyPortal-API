<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Models\Asset;
use App\Services\DataLoader\Importer\Importers\Assets\Importer;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Loader\Loader;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

/**
 * @template TOwner of \App\Utils\Eloquent\Model
 *
 * @mixin Loader
 */
trait WithAssets {
    protected bool $withAssets          = false;
    protected bool $withAssetsDocuments = false;

    public function isWithAssets(): bool {
        return $this->withAssets;
    }

    public function setWithAssets(bool $withAssets): static {
        $this->withAssets = $withAssets;

        return $this;
    }

    public function isWithAssetsDocuments(): bool {
        return $this->isWithAssets() && $this->withAssetsDocuments;
    }

    public function setWithAssetsDocuments(bool $withAssetsDocuments): static {
        $this->withAssetsDocuments = $withAssetsDocuments;

        return $this;
    }

    /**
     * @param TOwner $owner
     */
    protected function loadAssets(Model $owner): bool {
        // Update assets
        $date = Date::now();

        $this
            ->getAssetsImporter($owner)
            ->setWithDocuments($this->isWithAssetsDocuments())
            ->setFrom(null)
            ->setLimit(null)
            ->setChunkSize(null)
            ->start();

        // Update missed
        $iterator = $this->getMissedAssets($owner, $date)->getChangeSafeIterator();
        $iterator = new EloquentIterator($iterator);

        $this
            ->getContainer()
            ->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setFrom(null)
            ->setLimit(null)
            ->setChunkSize(null)
            ->start();

        // Return
        return true;
    }

    /**
     * @param TOwner $owner
     */
    abstract protected function getAssetsImporter(Model $owner): Importer;

    /**
     * @param TOwner $owner
     *
     * @return Builder<Asset>
     */
    abstract protected function getMissedAssets(Model $owner, DateTimeInterface $datetime): Builder;
}
