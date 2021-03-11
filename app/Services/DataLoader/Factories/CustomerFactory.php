<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Location as LocationModel;
use App\Models\Status as StatusModel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\DataLoaderException;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithLocations;
use App\Services\DataLoader\Factories\Concerns\WithStatus;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use Closure;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function in_array;
use function iterator_to_array;
use function reset;
use function sprintf;

// TODO [DataLoader] Customer can be a CUSTOMER or RESELLER or any other type.
//      If this is not true we need to update this factory and its tests.

class CustomerFactory extends ModelFactory {
    use WithType;
    use WithStatus;
    use WithContacts;
    use WithLocations;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeResolver $types,
        protected StatusResolver $statuses,
        protected CustomerResolver $customers,
    ) {
        parent::__construct($logger, $normalizer);
    }

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
                Company::class,
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromCompany(Company $company): Customer {
        // Get/Create customer
        $type     = $this->customerType($company->companyTypes);
        $status   = $this->customerStatus($company->companyTypes);
        $created  = false;
        $factory  = $this->factory(function (Customer $customer) use (&$created, $company, $type, $status): Customer {
            $created          = !$customer->exists;
            $customer->id     = $company->id;
            $customer->name   = $this->normalizer->string($company->name);
            $customer->type   = $type;
            $customer->status = $status;

            if ($this->contacts) {
                $customer->contacts = $this->objectContacts($customer, $company->companyContactPersons);
            }

            if ($this->locations) {
                $customer->locations = $this->objectLocations($customer, $company->locations);
            }

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

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyType> $types
     */
    protected function customerType(array $types): TypeModel {
        $type  = null;
        $names = array_unique(array_map(static function (CompanyType $type): string {
            return $type->type;
        }, $types));

        if (count($names) > 1) {
            throw new DataLoaderException('Multiple type.');
        } elseif (count($names) < 1) {
            throw new DataLoaderException('Type is missing.');
        } else {
            $type = $this->type(new Customer(), reset($names));
        }

        return $type;
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyType> $statuses
     */
    protected function customerStatus(array $statuses): StatusModel {
        $status = null;
        $names  = array_unique(array_map(static function (CompanyType $type): string {
            return $type->status;
        }, $statuses));

        if (count($names) > 1) {
            throw new DataLoaderException('Multiple status.');
        } elseif (count($names) < 1) {
            throw new DataLoaderException('Status is missing.');
        } else {
            $status = $this->status(new Customer(), reset($names));
        }

        return $status;
    }
    //</editor-fold>
}
