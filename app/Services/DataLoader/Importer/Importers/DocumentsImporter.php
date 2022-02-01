<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\ChunkData;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\AssetLoaderFinder;
use App\Services\DataLoader\Importer\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Importer\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\Status;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Utils\Iterators\ObjectIterator;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class DocumentsImporter extends Importer {
    protected function onRegister(): void {
        parent::onRegister();

        $this->container->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->container->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->container->bind(CustomerFinder::class, CustomerLoaderFinder::class);
        $this->container->bind(AssetFinder::class, AssetLoaderFinder::class);
    }

    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $data     = new ChunkData($items);
        $contacts = $this->container->make(ContactResolver::class);

        $this->container
            ->make(DocumentResolver::class)
            ->prefetch($data->getDocuments(), static function (Collection $assets) use ($contacts): void {
                $assets->loadMissing('contacts');

                $contacts->add($assets->pluck('contacts')->flatten());
            });

        $this->container
            ->make(ResellerResolver::class)
            ->prefetch($data->getResellers(), static function (Collection $resellers) use ($contacts): void {
                $resellers->loadMissing('contacts');

                $contacts->add($resellers->pluck('contacts')->flatten());
            });

        $this->container
            ->make(CustomerResolver::class)
            ->prefetch($data->getCustomers(), static function (Collection $customers) use ($contacts): void {
                $customers->loadMissing('contacts');

                $contacts->add($customers->pluck('contacts')->flatten());
            });

        (new Collection($contacts->getResolved()))->loadMissing('types');
    }

    protected function makeIterator(DateTimeInterface $from = null): ObjectIterator {
        return $this->client->getDocuments($from);
    }

    protected function makeLoader(): Loader {
        return $this->container->make(DocumentLoader::class);
    }

    protected function makeResolver(): Resolver {
        return $this->container->make(DocumentResolver::class);
    }

    protected function getObjectsCount(DateTimeInterface $from = null): ?int {
        return $from ? null : $this->client->getDocumentsCount();
    }
}
