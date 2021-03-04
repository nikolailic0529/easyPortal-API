<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location as LocationModel;
use App\Models\Model;
use App\Models\Status as StatusModel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\DataLoaderException;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\ContactProvider;
use App\Services\DataLoader\Providers\CustomerProvider;
use App\Services\DataLoader\Providers\StatusProvider;
use App\Services\DataLoader\Providers\TypeProvider;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_null;
use function iterator_to_array;
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
        protected ContactProvider $contacts,
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
     * @return array<\App\Models\CustomerLocation>
     */
    protected function customerLocations(Customer $customer, array $locations): array {
        $models = [];
        $object = new CustomerLocation();

        foreach ($locations as $location) {
            $loc   = $this->location($location);
            $type  = $this->type($object, $location->locationType);
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
                    'customer' => $customer,
                    'location' => $location,
                ]);
            } else {
                $models[$key] = $model;
            }
        }

        return array_values($models);
    }

    /**
     * @param array<\App\Services\DataLoader\Schema\CompanyContactPerson> $persons
     *
     * @return array<\App\Models\Contact>
     */
    protected function customerContacts(Customer $customer, array $persons): array {
        // First, we should convert CompanyContactPerson into the internal model
        // and determine its types.
        /** @var SplObjectStorage<\App\Models\Contact, array<\App\Models\Type>> $contacts */
        $contacts = new SplObjectStorage();
        $object   = new Contact();

        foreach ($persons as $person) {
            // CompanyContactPerson can be without name and phone
            if (is_null($person->name) && is_null($person->phoneNumber)) {
                continue;
            }

            // Phone can be in a local format we should convert it into e164 if
            // this is not possible we save it as is and mark it as invalid.
            $phone = $person->phoneNumber;
            $valid = false;

            if ($phone) {
                try {
                    $phone = PhoneNumber::make($phone)->formatE164();
                    $valid = true;
                } catch (NumberParseException) {
                    $valid = false;
                }
            } else {
                $phone = null;
                $valid = null;
            }

            // Search contact and determine type
            $type    = $this->type($object, $person->type);
            $contact = $this->contact($customer, $person->name, $phone, $valid);

            // Save
            if ($contacts->contains($contact)) {
                if (in_array($type, $contacts[$contact], true)) {
                    $this->logger->warning('Found customer with multiple contacts with the same type.', [
                        'customer' => $customer,
                        'contact'  => $contact,
                    ]);
                } else {
                    $contacts[$contact] = array_merge($contacts[$contact], [$type]);
                }
            } else {
                $contacts[$contact] = [$type];
            }
        }

        // Attach types into contacts
        foreach ($contacts as $contact) {
            $contact->types = $contacts[$contact];
        }

        // Return
        return iterator_to_array($contacts);
    }

    protected function location(Location $location): LocationModel {
        return $this->locations->create($location);
    }

    protected function contact(Customer $customer, ?string $name, ?string $phone, ?bool $valid): Contact {
        $contact = $this->contacts->get(
            $customer,
            $name,
            $phone,
            function () use ($customer, $name, $phone, $valid): Contact {
                $model = new Contact();

                if (!is_null($name)) {
                    $name = $this->normalizer->string($name);
                }

                if (!is_null($phone)) {
                    $phone = $this->normalizer->string($phone);
                }

                $model->object_type  = $customer->getMorphClass();
                $model->object_id    = $customer->getKey();
                $model->name         = $name;
                $model->phone_number = $phone;
                $model->phone_valid  = $valid;

                $model->save();

                return $model;
            },
        );

        return $contact;
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
