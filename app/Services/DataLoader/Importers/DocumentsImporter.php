<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\GraphQL\Utils\Iterators\QueryIterator;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\DocumentFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\AssetLoaderFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\DocumentLoader;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
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
        $contacts = $this->container->make(ContactResolver::class);

        $this->container
            ->make(DocumentFactory::class)
            ->prefetch($items, false, static function (Collection $assets) use ($contacts): void {
                $assets->loadMissing('contacts');

                $contacts->add($assets->pluck('contacts')->flatten());
            });

        $this->container
            ->make(ResellerFactory::class)
            ->prefetch($items, false, static function (Collection $resellers) use ($contacts): void {
                $resellers->loadMissing('contacts');

                $contacts->add($resellers->pluck('contacts')->flatten());
            });

        $this->container
            ->make(CustomerFactory::class)
            ->prefetch($items, false, static function (Collection $customers) use ($contacts): void {
                $customers->loadMissing('contacts');

                $contacts->add($customers->pluck('contacts')->flatten());
            });

        (new Collection($contacts->getResolved()))->loadMissing('types');
    }

    protected function makeIterator(DateTimeInterface $from = null): QueryIterator {
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
