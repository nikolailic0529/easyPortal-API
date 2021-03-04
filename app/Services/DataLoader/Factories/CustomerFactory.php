<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Models\Status as StatusModel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\DataLoaderException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\CustomerProvider;
use App\Services\DataLoader\Providers\StatusProvider;
use App\Services\DataLoader\Providers\TypeProvider;
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

/**
 * @internal
 */
class CustomerFactory extends ModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeProvider $types,
        protected StatusProvider $statuses,
        protected LocationFactory $locations,
        protected CustomerProvider $customers,
        protected ContactFactory $contacts,
    ) {
        parent::__construct($logger, $normalizer);
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

    protected function createFromCompany(Company $company): Customer {
        // Get/Create customer
        $type     = $this->customerType($company->companyTypes);
        $status   = $this->customerStatus($company->companyTypes);
        $customer = $this->customers->get($company->id, function () use ($company, $type, $status): Customer {
            $customer         = new Customer();
            $customer->id     = $company->id;
            $customer->name   = $this->normalizer->string($company->name);
            $customer->type   = $type;
            $customer->status = $status;

            $customer->save();

            return $customer;
        });

        // Update
        $customer->name      = $this->normalizer->string($company->name);
        $customer->type      = $type;
        $customer->status    = $status;
        $customer->contacts  = $this->customerContacts($customer, $company->companyContactPersons);
        $customer->locations = $this->customerLocations($customer, $company->locations);

        $customer->save();

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

    /**
     * @param array<\App\Services\DataLoader\Schema\Location> $locations
     *
     * @return array<\App\Models\Location>
     */
    protected function customerLocations(Customer $customer, array $locations): array {
        return $this->polymorphic(
            $customer,
            $locations,
            static function (Location $location): string {
                return $location->locationType;
            },
            function (Customer $customer, Location $location): ?LocationModel {
                return $this->location($customer, $location);
            },
        );
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyContactPerson> $persons
     *
     * @return array<\App\Models\Contact>
     */
    protected function customerContacts(Customer $customer, array $persons): array {
        return $this->polymorphic(
            $customer,
            $persons,
            static function (CompanyContactPerson $person): string {
                return $person->type;
            },
            function (Customer $customer, CompanyContactPerson $person): ?Contact {
                return $this->contact($customer, $person);
            },
        );
    }

    protected function location(Customer $customer, Location $location): ?LocationModel {
        return $this->locations->create($customer, $location);
    }

    protected function contact(Customer $customer, CompanyContactPerson $person): ?Contact {
        return $this->contacts->create($customer, $person);
    }

    protected function type(Model $owner, string $type): TypeModel {
        $type = $this->types->get($owner, $type, function () use ($owner, $type): TypeModel {
            $model = new TypeModel();

            $model->object_type = $owner->getMorphClass();
            $model->key         = $this->normalizer->string($type);
            $model->name        = $this->normalizer->string($type);

            $model->save();

            return $model;
        });

        return $type;
    }

    protected function status(Model $owner, string $status): StatusModel {
        $status = $this->statuses->get($owner, $status, function () use ($owner, $status): StatusModel {
            $model = new StatusModel();

            $model->object_type = $owner->getMorphClass();
            $model->key         = $this->normalizer->string($status);
            $model->name        = $this->normalizer->string($status);

            $model->save();

            return $model;
        });

        return $status;
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\Type> $types
     *
     * @return array<mixed>
     */
    private function polymorphic(Customer $customer, array $types, Closure $getType, Closure $factory): array {
        // First, we should convert type into the internal model and determine its types.
        /** @var \SplObjectStorage<\App\Models\Contact|\App\Models\Location, array<\App\Models\Type>> $models */
        $models = new SplObjectStorage();

        foreach ($types as $object) {
            // Search contact
            $model = $factory($customer, $object);

            if (!$model) {
                $this->logger->warning('Found invalid contact.', [
                    'customer' => $customer,
                    'object'   => $object,
                ]);

                continue;
            }

            // Determine type
            $type = $this->type($model, $getType($object));

            if ($models->contains($model)) {
                if (in_array($type, $models[$model], true)) {
                    $this->logger->warning('Found customer with multiple models with the same type.', [
                        'customer' => $customer,
                        'object'   => $object,
                        'model'    => $model,
                        'type'     => $type,
                    ]);
                } else {
                    $models[$model] = array_merge($models[$model], [$type]);
                }
            } else {
                $models[$model] = [$type];
            }
        }

        // Attach types into models
        foreach ($models as $model) {
            $model->types = $models[$model];
        }

        // Return
        return iterator_to_array($models);
    }
}
