<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Models\Asset;
use App\Services\DataLoader\Importer\Importers\Documents\Importer;
use App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter;
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
trait WithDocuments {
    protected bool $withDocuments = false;

    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }

    /**
     * @param TOwner $owner
     */
    protected function loadDocuments(Model $owner): bool {
        // Update
        $date = Date::now();

        $this
            ->getDocumentsImporter($owner)
            ->setFrom(null)
            ->setLimit(null)
            ->setChunkSize(null)
            ->start();

        // Update missed
        $iterator = $this->getMissedDocuments($owner, $date)->getChangeSafeIterator();
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
    abstract protected function getDocumentsImporter(Model $owner): Importer;

    /**
     * @param TOwner $owner
     *
     * @return Builder<Asset>
     */
    abstract protected function getMissedDocuments(Model $owner, DateTimeInterface $datetime): Builder;
}
