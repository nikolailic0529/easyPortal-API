<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Factory\CompanyFactory;
use App\Services\DataLoader\Factory\Concerns\WithKpi;
use App\Services\DataLoader\Factory\Concerns\WithReseller;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function implode;
use function sprintf;

/**
 * @extends CompanyFactory<Customer, CustomerLocation>
 */
class CustomerFactory extends CompanyFactory {
    use WithKpi;
    use WithReseller;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        TypeResolver $typeResolver,
        StatusResolver $statusResolver,
        ContactResolver $contactReseller,
        LocationFactory $locationFactory,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected ?ResellerFinder $resellerFinder = null,
    ) {
        parent::__construct(
            $exceptionHandler,
            $typeResolver,
            $statusResolver,
            $contactReseller,
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
    public function getModel(): string {
        return Customer::class;
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

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Customer {
        // Get/Create customer
        $created  = false;
        $factory  = function (Customer $customer) use (&$created, $company): Customer {
            // Unchanged?
            $created = !$customer->exists;
            $hash    = $company->getHash();

            if ($hash === $customer->hash) {
                return $customer;
            }

            // Update
            $customer->id              = $company->id;
            $customer->hash            = $hash;
            $customer->name            = $company->name;
            $customer->changed_at      = $company->updatedAt;
            $customer->statuses        = $this->companyStatuses($customer, $company);
            $customer->contacts        = $this->objectContacts($customer, $company->companyContactPersons);
            $customer->locations       = $this->companyLocations($customer, $company->locations);
            $customer->kpi             = $this->kpi($customer, $company->companyKpis);
            $customer->resellersPivots = $this->resellers($customer, $company->companyResellerKpis);

            if ($customer->trashed()) {
                $customer->restore();
            } else {
                $customer->save();
            }

            return $customer;
        };
        $customer = $this->customerResolver->get($company->id, static function () use ($factory): Customer {
            return $factory(new Customer());
        });

        // Update
        if (!$created) {
            $factory($customer);
        }

        // Return
        return $customer;
    }

    /**
     * @param array<CompanyKpis> $kpis
     *
     * @return Collection<int, ResellerCustomer>
     */
    protected function resellers(Customer $customer, array $kpis = null): Collection {
        /** @var Collection<int, ResellerCustomer> $existing https://github.com/phpstan/phpstan/issues/6849 */
        $existing = $customer->resellersPivots->keyBy(
            $customer->resellers()->getRelatedPivotKeyName(),
        );
        $pivots   = new Collection();

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
