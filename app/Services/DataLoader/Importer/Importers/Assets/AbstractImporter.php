<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Assets;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\AssetFactory;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Importer\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

use function array_merge;

/**
 * @template TState of AbstractImporterState
 *
 * @extends Importer<ViewAsset, ImporterChunkData, TState, Asset>
 */
abstract class AbstractImporter extends Importer {
    private bool $withDocuments = true;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function register(): void {
        $this->getContainer()->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bind(CustomerFinder::class, CustomerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data      = new ImporterChunkData($items);
        $container = $this->getContainer();
        $locations = $container->make(LocationResolver::class);
        $contacts  = $container->make(ContactResolver::class);

        $container
            ->make(AssetResolver::class)
            ->prefetch(
                $data->get(Asset::class),
                static function (Collection $assets) use ($locations, $contacts): void {
                    $assets->loadMissing('warranties.serviceLevels');
                    $assets->loadMissing('warranties.document');
                    $assets->loadMissing('contacts.types');
                    $assets->loadMissing('location');
                    $assets->loadMissing('tags');
                    $assets->loadMissing('oem');

                    $locations->put($assets->pluck('locations')->flatten());
                    $contacts->put($assets->pluck('contacts')->flatten());
                },
            );

        $container
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class), static function (Collection $resellers) use ($locations): void {
                $resellers->loadMissing('locations.location');

                $locations->put($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
            });

        $container
            ->make(CustomerResolver::class)
            ->prefetch($data->get(Customer::class), static function (Collection $customers) use ($locations): void {
                $customers->loadMissing('locations.location');

                $locations->put($customers->pluck('locations')->flatten()->pluck('location')->flatten());
            });

        if ($state->withDocuments) {
            $container
                ->make(DocumentResolver::class)
                ->prefetch($data->get(Document::class));
        }

        return $data;
    }

    protected function makeFactory(State $state): ModelFactory {
        $factory = $this->getContainer()->make(AssetFactory::class);

        if ($state->withDocuments) {
            $factory->setDocumentFactory(
                $this->getContainer()->make(DocumentFactory::class),
            );
        }

        return $factory;
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(AssetResolver::class);
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new AbstractImporterState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'withDocuments' => $this->isWithDocuments(),
        ]);
    }
    // </editor-fold>
}
