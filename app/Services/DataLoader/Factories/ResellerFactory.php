<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Factories\Concerns\Company as ConcernsCompany;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithLocations;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function implode;
use function sprintf;

class ResellerFactory extends ModelFactory {
    use WithLocations;
    use WithType;
    use WithContacts;
    use ConcernsCompany;

    protected ?LocationFactory $locations = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeResolver $types,
        protected ResellerResolver $resellers,
        protected Dispatcher $events,
        protected StatusResolver $statuses,
    ) {
        parent::__construct($logger, $normalizer);
    }

    // <editor-fold desc="Settings">
    // =========================================================================
    public function setLocationFactory(?LocationFactory $factory): static {
        $this->locations = $factory;

        return $this;
    }

    protected function shouldUpdateLocations(): bool {
        return (bool) $this->locations;
    }
    // </editor-fold>

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Company|\App\Services\DataLoader\Schema\Asset> $resellers
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $resellers, bool $reset = false, Closure|null $callback = null): static {
        $keys = array_unique(array_filter(array_map(static function (Company|Asset $model): ?string {
            if ($model instanceof Company) {
                return $model->id;
            } elseif ($model instanceof Asset) {
                return $model->resellerId;
            } else {
                return null;
            }
        }, $resellers)));

        $this->resellers->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function find(Type $type): ?Reseller {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Reseller {
        $model = null;

        if ($type instanceof AssetDocumentObject) {
            $model = $this->createFromAssetDocumentObject($type);
        } elseif ($type instanceof AssetDocument) {
            $model = $this->createFromAssetDocument($type);
        } elseif ($type instanceof Document) {
            $model = $this->createFromDocument($type);
        } elseif ($type instanceof Company) {
            $model = $this->createFromCompany($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    AssetDocumentObject::class,
                    AssetDocument::class,
                    Document::class,
                    Company::class,
                ]),
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromAssetDocumentObject(AssetDocumentObject $document): ?Reseller {
        $reseller = null;

        if (isset($document->document->document)) {
            $reseller = $this->createFromDocument($document->document->document);
        }

        if (!$reseller) {
            $reseller = $this->createFromAssetDocument($document->document);
        }

        return $reseller;
    }

    protected function createFromAssetDocument(AssetDocument $document): ?Reseller {
        return isset($document->reseller) && $document->reseller
            ? $this->createFromCompany($document->reseller)
            : null;
    }

    protected function createFromDocument(Document $document): ?Reseller {
        return isset($document->reseller) && $document->reseller
            ? $this->createFromCompany($document->reseller)
            : null;
    }

    protected function createFromCompany(Company $company): ?Reseller {
        // Get/Create
        $created  = false;
        $factory  = $this->factory(function (Reseller $reseller) use (&$created, $company): Reseller {
            $created          = !$reseller->exists;
            $reseller->id     = $this->normalizer->uuid($company->id);
            $reseller->name   = $this->normalizer->string($company->name);
            $reseller->type   = $this->companyType($reseller, $company->companyTypes);
            $reseller->status = $this->companyStatus($reseller, $company->companyTypes);

            if ($this->locations) {
                $reseller->locations = $this->objectLocations($reseller, $company->locations);
            }

            if ($this->contacts) {
                $reseller->contacts = $this->objectContacts($reseller, $company->companyContactPersons);
            }

            $reseller->save();

            $this->events->dispatch(new ResellerUpdated($reseller, $company));

            return $reseller;
        });
        $reseller = $this->resellers->get(
            $company->id,
            static function () use ($factory): Reseller {
                return $factory(new Reseller());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($reseller);
        }

        // Return
        return $reseller;
    }
    // </editor-fold>
}
