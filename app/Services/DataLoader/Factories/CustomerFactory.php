<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Customer;
use App\Services\DataLoader\Factories\Concerns\Company as ConcernsCompany;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithLocations;
use App\Services\DataLoader\Factories\Concerns\WithStatus;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use Closure;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function implode;
use function sprintf;

// TODO [DataLoader] Customer can be a CUSTOMER or RESELLER or any other type.
//      If this is not true we need to update this factory and its tests.

class CustomerFactory extends ModelFactory implements FactoryPrefetchable {
    use WithType;
    use WithStatus;
    use WithContacts;
    use WithLocations;
    use ConcernsCompany;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeResolver $types,
        protected StatusResolver $statuses,
        protected CustomerResolver $customers,
        protected ContactFactory $contacts,
        protected LocationFactory $locations,
    ) {
        parent::__construct($logger, $normalizer);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getContactsFactory(): ContactFactory {
        return $this->contacts;
    }

    protected function getLocationFactory(): LocationFactory {
        return $this->locations;
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
     * @param array<\App\Services\DataLoader\Schema\Company|\App\Services\DataLoader\Schema\ViewAsset> $objects
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $objects, bool $reset = false, Closure|null $callback = null): static {
        $keys = array_unique(array_filter(array_map(static function (Company|ViewAsset $model): ?string {
            if ($model instanceof Company) {
                return $model->id;
            } elseif ($model instanceof ViewAsset) {
                return $model->customerId;
            } else {
                return null;
            }
        }, $objects)));

        $this->customers->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): ?Customer {
        // Get/Create customer
        $created  = false;
        $factory  = $this->factory(function (Customer $customer) use (&$created, $company): Customer {
            $created             = !$customer->exists;
            $customer->id        = $this->normalizer->uuid($company->id);
            $customer->name      = $this->normalizer->string($company->name);
            $customer->type      = $this->companyType($customer, $company->companyTypes);
            $customer->status    = $this->companyStatus($customer, $company->companyTypes);
            $customer->contacts  = $this->objectContacts($customer, $company->companyContactPersons);
            $customer->locations = $this->objectLocations($customer, $company->locations);

            $customer->save();

            return $customer;
        });
        $customer = $this->customers->get($company->id, static function () use ($factory): Customer {
            return $factory(new Customer());
        });

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($customer);
        }

        // Return
        return $customer;
    }
    //</editor-fold>
}
