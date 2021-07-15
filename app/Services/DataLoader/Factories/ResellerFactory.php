<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function implode;
use function sprintf;

class ResellerFactory extends CompanyFactory implements FactoryPrefetchable {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        Dispatcher $dispatcher,
        TypeResolver $types,
        StatusResolver $statuses,
        ContactFactory $contacts,
        LocationFactory $locations,
        protected ResellerResolver $resellers,
    ) {
        parent::__construct($logger, $normalizer, $dispatcher, $types, $statuses, $contacts, $locations);
    }

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Company|\App\Services\DataLoader\Schema\ViewAsset> $objects
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $objects, bool $reset = false, Closure|null $callback = null): static {
        $keys = (new Collection($objects))
            ->map(static function (Company|ViewAsset $model): array {
                $keys = [];

                if ($model instanceof Company) {
                    $keys[] = $model->id;
                } elseif ($model instanceof ViewAsset) {
                    $keys[] = $model->resellerId;

                    if (isset($model->assetDocument)) {
                        foreach ($model->assetDocument as $assetDocument) {
                            $keys[] = $assetDocument->reseller->id ?? null;
                            $keys[] = $assetDocument->document->resellerId ?? null;
                        }
                    }
                } else {
                    // empty
                }

                return $keys;
            })
            ->flatten()
            ->unique()
            ->filter()
            ->all();

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

        if ($type instanceof Company) {
            $model = $this->createFromCompany($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    Company::class,
                ]),
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Reseller {
        // Get/Create
        $created  = false;
        $factory  = $this->factory(function (Reseller $reseller) use (&$created, $company): Reseller {
            $created              = !$reseller->exists;
            $reseller->id         = $this->normalizer->uuid($company->id);
            $reseller->name       = $this->normalizer->string($company->name);
            $reseller->type       = $this->companyType($reseller, $company->companyTypes);
            $reseller->changed_at = $this->normalizer->datetime($company->updatedAt);
            $reseller->statuses   = $this->companyStatuses($reseller, $company);
            $reseller->contacts   = $this->objectContacts($reseller, $company->companyContactPersons);
            $reseller->locations  = $this->objectLocations($reseller, $company->locations);

            $reseller->save();

            $this->getDispatcher()->dispatch(new ResellerUpdated($reseller, $company));

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
