<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Factories\Concerns\WithKpi;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function implode;
use function sprintf;

class ResellerFactory extends CompanyFactory implements FactoryPrefetchable {
    use WithKpi;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        TypeResolver $typeResolver,
        StatusResolver $statusResolver,
        ContactFactory $contactFactory,
        LocationFactory $locationFactory,
        protected Dispatcher $dispatcher,
        protected ResellerResolver $resellerResolver,
    ) {
        parent::__construct(
            $exceptionHandler,
            $normalizer,
            $typeResolver,
            $statusResolver,
            $contactFactory,
            $locationFactory,
        );
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

        $this->resellerResolver->prefetch($keys, $reset, $callback);

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
            $created                   = !$reseller->exists;
            $normalizer                = $this->getNormalizer();
            $reseller->id              = $normalizer->uuid($company->id);
            $reseller->name            = $normalizer->string($company->name);
            $reseller->type            = $this->companyType($reseller, $company->companyTypes);
            $reseller->changed_at      = $normalizer->datetime($company->updatedAt);
            $reseller->assets_count    = 0;
            $reseller->customers_count = 0;
            $reseller->statuses        = $this->companyStatuses($reseller, $company);
            $reseller->contacts        = $this->objectContacts($reseller, $company->companyContactPersons);
            $reseller->locations       = $this->companyLocations($reseller, $company->locations);
            $reseller->kpi             = $this->kpi($reseller, $company->companyKpis);

            $reseller->save();

            $this->dispatcher->dispatch(new ResellerUpdated($reseller, $company));

            return $reseller;
        });
        $reseller = $this->resellerResolver->get(
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
