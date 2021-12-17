<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Customer;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Factories\Concerns\WithKpi;
use App\Services\DataLoader\Factories\Concerns\WithReseller;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

use function implode;
use function sprintf;

// TODO [DataLoader] Customer can be a CUSTOMER or RESELLER or any other type.
//      If this is not true we need to update this factory and its tests.

class CustomerFactory extends CompanyFactory implements FactoryPrefetchable {
    use WithKpi;
    use WithReseller;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        TypeResolver $typeResolver,
        StatusResolver $statusResolver,
        ContactFactory $contactFactory,
        LocationFactory $locationFactory,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected ?ResellerFinder $resellerFinder = null,
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

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getResellerFinder(): ?ResellerFinder {
        return $this->resellerFinder;
    }

    protected function getResellerResolver(): ResellerResolver {
        return $this->resellerResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function find(Type $type): ?Customer {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    public function create(Type $type): ?Customer {
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

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Company
     *          |\App\Services\DataLoader\Schema\Document
     *          |\App\Services\DataLoader\Schema\ViewAsset> $objects
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $objects, bool $reset = false, Closure|null $callback = null): static {
        $keys = (new Collection($objects))
            ->map(static function (Company|Document|ViewAsset $model): array {
                $keys = [];

                if ($model instanceof Company) {
                    $keys[] = $model->id;
                } elseif ($model instanceof Document) {
                    $keys[] = $model->customerId;
                } elseif ($model instanceof ViewAsset) {
                    $keys[] = $model->customerId;

                    if (isset($model->assetDocument)) {
                        foreach ($model->assetDocument as $assetDocument) {
                            $keys[] = $assetDocument->customer->id ?? null;
                            $keys[] = $assetDocument->document->customerId ?? null;
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

        $this->customerResolver->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Customer {
        // Get/Create customer
        $created  = false;
        $factory  = $this->factory(function (Customer $customer) use (&$created, $company): Customer {
            $created                   = !$customer->exists;
            $normalizer                = $this->getNormalizer();
            $customer->id              = $normalizer->uuid($company->id);
            $customer->name            = $normalizer->string($company->name);
            $customer->type            = $this->companyType($customer, $company->companyTypes);
            $customer->changed_at      = $normalizer->datetime($company->updatedAt);
            $customer->statuses        = $this->companyStatuses($customer, $company);
            $customer->contacts        = $this->objectContacts($customer, $company->companyContactPersons);
            $customer->locations       = $this->companyLocations($customer, $company->locations);
            $customer->kpi             = $this->kpi($customer, $company->companyKpis);
            $customer->synced_at       = Date::now();
            $customer->resellersPivots = $this->resellers($customer, $company->companyResellerKpis);

            if ($created) {
                $customer->assets_count = 0;
            }

            $customer->save();

            return $customer;
        });
        $customer = $this->customerResolver->get($company->id, static function () use ($factory): Customer {
            return $factory(new Customer());
        });

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($customer);
        }

        // Return
        return $customer;
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyKpis> $kpis
     *
     * @return \Illuminate\Support\Collection<\App\Models\ResellerCustomer>
     */
    protected function resellers(Customer $customer, array $kpis = null): Collection {
        $pivots   = new Collection();
        $existing = $customer->resellersPivots->keyBy(
            $customer->resellers()->getRelatedPivotKeyName(),
        );

        foreach ((array) $kpis as $kpi) {
            // Reseller?
            $reseller = $this->reseller($kpi);

            if (!$reseller) {
                continue;
            }

            // Exists?
            $id         = $reseller->getKey();
            $pivot      = $existing->get($id) ?: new ResellerCustomer();
            $pivot->kpi = $this->kpi($pivot, $kpi);

            $pivots[$id] = $pivot;

            // Mark
            unset($existing[$id]);
        }

        foreach ($existing as $reseller => $pivot) {
            $pivot->kpi        = null;
            $pivots[$reseller] = $pivot;
        }

        return $pivots;
    }
    //</editor-fold>
}
