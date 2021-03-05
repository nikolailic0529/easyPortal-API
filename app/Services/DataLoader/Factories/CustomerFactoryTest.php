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
use App\Services\DataLoader\Providers\TypeProvider;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Location;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Exception;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use libphonenumber\NumberParseException;
use Mockery;
use Propaganistas\LaravelPhone\PhoneNumber;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

use function array_map;
use function array_unique;
use function array_values;
use function is_null;
use function reset;
use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\CustomerFactory
 */
class CustomerFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app
            ->make(CustomerFactory::class)
            ->setLocationFactory($this->app->make(LocationFactory::class))
            ->setContactsFactory($this->app->make(ContactFactory::class));
        $json    = $this->getTestData()->json('~customer.json');
        $company = Company::create($json);

        $this->flushQueryLog();

        $factory->find($company);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(CustomerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type)
                ->andReturns();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompany(): void {
        // Helpers
        $getCompanyType       = static function (Company $company): string {
            $types = array_unique(array_map(static function (CompanyType $type): string {
                return $type->type;
            }, $company->companyTypes));

            return reset($types);
        };
        $getCompanyLocations  = static function (Company $company): array {
            $locations = [];

            foreach ($company->locations as $location) {
                // Add to array
                $key = "{$location->zip}/{$location->zip}/{$location->address}";

                if (isset($locations[$key])) {
                    $locations[$key]['types'][] = $location->locationType;
                } else {
                    $locations[$key] = [
                        'types'    => [$location->locationType],
                        'postcode' => $location->zip,
                        'state'    => '',
                        'city'     => $location->city,
                        'line_one' => $location->address,
                        'line_two' => '',
                    ];
                }
            }

            return array_values($locations);
        };
        $getCustomerLocations = static function (Customer $customer): array {
            $locations = [];

            foreach ($customer->locations as $location) {
                /** @var \App\Models\Location $location */
                $locations[] = [
                    'postcode' => $location->postcode,
                    'state'    => $location->state,
                    'city'     => $location->city->name,
                    'line_one' => $location->line_one,
                    'line_two' => $location->line_two,
                    'types'    => $location->types
                        ->map(static function (TypeModel $type): string {
                            return $type->name;
                        })
                        ->all(),
                ];
            }

            return $locations;
        };
        $getCompanyContacts   = static function (Company $company): array {
            $contacts = [];

            foreach ($company->companyContactPersons as $person) {
                // Empty?
                if (is_null($person->name) && is_null($person->phoneNumber)) {
                    continue;
                }

                // Convert phone
                $phone = $person->phoneNumber;

                try {
                    $phone = PhoneNumber::make($phone)->formatE164();
                } catch (NumberParseException) {
                    // empty
                }

                // Add to array
                $key = "{$person->name}/{$phone}";

                if (isset($contacts[$key])) {
                    $contacts[$key]['types'][] = $person->type;
                } else {
                    $contacts[$key] = [
                        'name'  => $person->name,
                        'phone' => $phone,
                        'types' => [$person->type],
                    ];
                }
            }

            return $contacts;
        };
        $getCustomerContacts  = static function (Customer $customer): array {
            $contacts = [];

            foreach ($customer->contacts as $contact) {
                $contacts["{$contact->name}/{$contact->phone_number}"] = [
                    'name'  => $contact->name,
                    'phone' => $contact->phone_number,
                    'types' => $contact->types
                        ->map(static function (TypeModel $type): string {
                            return $type->name;
                        })
                        ->all(),
                ];
            }

            return $contacts;
        };

        // Prepare
        $factory = $this->app
            ->make(CustomerFactory::class)
            ->setLocationFactory($this->app->make(LocationFactory::class))
            ->setContactsFactory($this->app->make(ContactFactory::class));

        // Test
        $file     = $this->faker->randomElement(['~customer.json', '~reseller.json']);
        $json     = $this->getTestData()->json($file);
        $company  = Company::create($json);
        $customer = $factory->create($company);

        $this->assertNotNull($customer);
        $this->assertTrue($customer->wasRecentlyCreated);
        $this->assertEquals($company->id, $customer->getKey());
        $this->assertEquals($company->name, $customer->name);
        $this->assertEquals($getCompanyType($company), $customer->type->key);
        $this->assertCount(1, $customer->locations);
        $this->assertEqualsCanonicalizing(
            $getCompanyLocations($company),
            $getCustomerLocations($customer),
        );
        $this->assertCount(4, $customer->contacts);
        $this->assertEquals(
            $getCompanyContacts($company),
            $getCustomerContacts($customer),
        );

        // Customer should be updated
        $json    = $this->getTestData()->json('~customer-changed.json');
        $company = Company::create($json);
        $updated = $factory->create($company);

        $this->assertNotNull($updated);
        $this->assertSame($customer, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertEquals($getCompanyType($company), $updated->type->key);
        $this->assertCount(1, $updated->locations);
        $this->assertEqualsCanonicalizing(
            $getCompanyLocations($company),
            $getCustomerLocations($updated),
        );
        $this->assertCount(1, $updated->contacts);
        $this->assertEquals(
            $getCompanyContacts($company),
            $getCustomerContacts($updated),
        );
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyCustomerOnly(): void {
        // Prepare
        $factory = $this->app->make(CustomerFactory::class);

        // Test
        $json     = $this->getTestData()->json('~customer-only.json');
        $company  = Company::create($json);
        $customer = $factory->create($company);

        $this->assertNotNull($customer);
        $this->assertTrue($customer->wasRecentlyCreated);
        $this->assertEquals($company->id, $customer->getKey());
        $this->assertEquals($company->name, $customer->name);
    }

    /**
     * @covers ::customerType
     *
     * @dataProvider dataProviderCustomerType
     */
    public function testCustomerType(string|Exception $expected, Closure $typesFactory): void {
        // Prepare
        $factory = new class() extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritdoc
             */
            public function customerType(array $names): TypeModel {
                return parent::customerType($names);
            }

            protected function type(Model $model, string $type): TypeModel {
                return TypeModel::factory()->make([
                    'object_type' => $model,
                    'key'         => $type,
                ]);
            }
        };

        // Test
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $types  = $typesFactory($this);
        $actual = $factory->customerType($types);

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual->key);
    }

    /**
     * @covers ::customerStatus
     *
     * @dataProvider dataProviderCustomerStatus
     */
    public function testCustomerStatus(string|Exception $expected, Closure $statusesFactory): void {
        // Prepare
        $factory = new class() extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritdoc
             */
            public function customerStatus(array $statuses): StatusModel {
                return parent::customerStatus($statuses);
            }

            protected function status(Model $model, string $status): StatusModel {
                return StatusModel::factory()->make([
                    'object_type' => $model,
                    'key'         => $status,
                ]);
            }
        };

        // Test
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $statuses = $statusesFactory($this);
        $actual   = $factory->customerStatus($statuses);

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual->key);
    }

    public function testCustomerLocations(): void {
        // Prepare
        $customer = Customer::factory()->make();
        $existing = LocationModel::factory(2)->make([
            'object_type' => $customer->getMorphClass(),
            'object_id'   => $customer,
        ]);

        $customer->setRelation('locations', $existing);

        $factory = new class(
            $this->app->make(LoggerInterface::class),
            $this->app->make(Normalizer::class),
            $this->app->make(LocationFactory::class),
            $this->app->make(TypeProvider::class),
        ) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                LoggerInterface $logger,
                Normalizer $normalizer,
                LocationFactory $locations,
                TypeProvider $types,
            ) {
                $this->logger     = $logger;
                $this->normalizer = $normalizer;
                $this->locations  = $locations;
                $this->types      = $types;
            }

            /**
             * @inheritdoc
             */
            public function customerLocations(Customer $customer, array $locations): array {
                return parent::customerLocations($customer, $locations);
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->customerLocations($customer, []));

        // Repeated objects should be missed
        $ca = tap(new Location(), function (Location $location): void {
            $location->zip          = $this->faker->postcode;
            $location->city         = $this->faker->city;
            $location->address      = $this->faker->streetAddress;
            $location->locationType = $this->faker->word;
        });

        $this->assertCount(1, $factory->customerLocations($customer, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new Location(), function (Location $location) use ($ca): void {
            $location->zip          = $ca->zip;
            $location->city         = $ca->city;
            $location->address      = $ca->address;
            $location->locationType = $this->faker->word;
        });
        $actual = $factory->customerLocations($customer, [$ca, $cb]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(2, $first->types);
        $this->assertEquals($cb->zip, $first->postcode);
        $this->assertEquals($cb->city, $first->city->name);
        $this->assertEquals($cb->address, $first->line_one);
    }

    /**
     * @covers ::location
     */
    public function testLocation(): void {
        // Prepare
        $customer = new Customer();
        $location = new Location();
        $factory  = Mockery::mock(LocationFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($customer, $location)
            ->once()
            ->andReturns();

        $factory = new class($factory) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $locations) {
                $this->locations = $locations;
            }

            public function location(Customer $customer, Location $location): ?LocationModel {
                return parent::location($customer, $location);
            }
        };

        $factory->location($customer, $location);
    }

    /**
     * @covers ::customerContacts
     */
    public function testCustomerContacts(): void {
        // Prepare
        $customer = Customer::factory()->make();
        $existing = Contact::factory(2)->make([
            'object_type' => $customer->getMorphClass(),
            'object_id'   => $customer->getKey(),
        ]);

        $customer->setRelation('contacts', $existing);

        $factory = new class(
            $this->app->make(LoggerInterface::class),
            $this->app->make(Normalizer::class),
            $this->app->make(ContactFactory::class),
            $this->app->make(TypeProvider::class),
        ) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                LoggerInterface $logger,
                Normalizer $normalizer,
                ContactFactory $contacts,
                TypeProvider $types,
            ) {
                $this->logger     = $logger;
                $this->normalizer = $normalizer;
                $this->contacts   = $contacts;
                $this->types      = $types;
            }

            /**
             * @inheritdoc
             */
            public function customerContacts(Customer $customer, array $persons): array {
                return parent::customerContacts($customer, $persons);
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->customerContacts($customer, []));

        // Repeated objects should be missed
        $ca = tap(new CompanyContactPerson(), function (CompanyContactPerson $person): void {
            $person->name        = $this->faker->name;
            $person->type        = $this->faker->word;
            $person->phoneNumber = $this->faker->e164PhoneNumber;
        });

        $this->assertCount(1, $factory->customerContacts($customer, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new CompanyContactPerson(), function (CompanyContactPerson $person) use ($ca): void {
            $person->name        = $ca->name;
            $person->type        = $this->faker->word;
            $person->phoneNumber = $ca->phoneNumber;
        });
        $actual = $factory->customerContacts($customer, [$ca, $cb]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(2, $first->types);
        $this->assertEquals($cb->phoneNumber, $first->phone_number);
        $this->assertEquals($cb->name, $first->name);
    }

    /**
     * @covers ::contact
     */
    public function testContact(): void {
        // Prepare
        $customer = new Customer();
        $contact  = new CompanyContactPerson();
        $factory  = Mockery::mock(ContactFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($customer, $contact)
            ->once()
            ->andReturns();

        $factory = new class($factory) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ContactFactory $contacts) {
                $this->contacts = $contacts;
            }

            public function contact(Customer $customer, CompanyContactPerson $person): ?Contact {
                return parent::contact($customer, $person);
            }
        };

        $factory->contact($customer, $contact);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            Company::class => ['createFromCompany', new Company()],
            'Unknown'      => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderCustomerType(): array {
        return [
            'one value'                => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value';
                        }),
                    ];
                },
            ],
            'several values, but same' => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value';
                        }),
                    ];
                },
            ],
            'several values'           => [
                new DataLoaderException('Multiple type.'),
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value a';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value b';
                        }),
                    ];
                },
            ],
            'empty'                    => [
                new DataLoaderException('Type is missing.'),
                static function (): array {
                    return [];
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderCustomerStatus(): array {
        return [
            'one value'                => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value';
                        }),
                    ];
                },
            ],
            'several values, but same' => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value';
                        }),
                    ];
                },
            ],
            'several values'           => [
                new DataLoaderException('Multiple status.'),
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value a';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value b';
                        }),
                    ];
                },
            ],
            'empty'                    => [
                new DataLoaderException('Status is missing.'),
                static function (): array {
                    return [];
                },
            ],
        ];
    }
    // </editor-fold>
}
