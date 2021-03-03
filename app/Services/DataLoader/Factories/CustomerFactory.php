<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Models\Status as StatusModel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\DataLoaderException;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\CustomerProvider;
use App\Services\DataLoader\Providers\StatusProvider;
use App\Services\DataLoader\Providers\TypeProvider;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_map;
use function array_unique;
use function array_values;
use function count;
use function reset;
use function sprintf;

// TODO [DataLoader] Customer can be a CUSTOMER or RESELLER or any other type.
//      If this is not true we need to update this factory and its tests.

/**
 * @internal
 */
class CustomerFactory extends Factory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected TypeProvider $types,
        protected StatusProvider $statuses,
        protected LocationFactory $locations,
        protected CustomerProvider $customers,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function create(Type $type): Customer {
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
     * @return array<\App\Models\CustomerLocation>
     */
    protected function customerLocations(Customer $customer, array $locations): array {
        $models = [];

        foreach ($locations as $location) {
            $loc   = $this->location($location);
            $type  = $this->type(new CustomerLocation(), $location->locationType);
            $key   = "{$loc->getKey()}/{$type->getKey()}";
            $model = $customer->locations->first(
                static function (CustomerLocation $location) use ($loc, $type): bool {
                    return $location->location_id === $loc->getKey()
                        && $location->type_id === $type->getKey();
                },
            );

            if (!$model) {
                $model           = new CustomerLocation();
                $model->location = $loc;
                $model->type     = $type;
            }

            if (isset($models[$key])) {
                $this->logger->warning('Found customer with multiple locations with the same type.', [
                    'location' => $location,
                ]);
            } else {
                $models[$key] = $model;
            }
        }

        return array_values($models);
    }

    protected function location(Location $location): LocationModel {
        return $this->locations->create($location);
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
}
