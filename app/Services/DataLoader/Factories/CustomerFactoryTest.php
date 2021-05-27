<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Model;
use App\Models\Status as StatusModel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\CustomerFactory
 */
class CustomerFactoryTest extends TestCase {
    use WithoutOrganizationScope;
    use WithQueryLog;
    use Helper;

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
        $json    = $this->getTestData()->json('~customer-full.json');
        $company = new Company($json);

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
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObject(): void {
        $document = new AssetDocumentObject([
            'document' => [
                'customer' => [
                    'id' => $this->faker->uuid,
                ],
                'document' => [
                    'customer' => [
                        'id' => $this->faker->uuid,
                    ],
                ],
            ],
        ]);

        $factory = Mockery::mock(CustomerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('createFromDocument')
            ->once()
            ->with($document->document->document)
            ->andReturn(null);
        $factory
            ->shouldReceive('createFromAssetDocument')
            ->once()
            ->with($document->document)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromAssetDocument
     */
    public function testCreateFromAssetDocument(): void {
        $document = new AssetDocument([
            'customer' => [
                'id' => $this->faker->uuid,
            ],
        ]);

        $factory = Mockery::mock(CustomerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('createFromCompany')
            ->once()
            ->with($document->customer)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        $document = new Document([
            'customer' => [
                'id' => $this->faker->uuid,
            ],
        ]);

        $factory = Mockery::mock(CustomerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('createFromCompany')
            ->once()
            ->with($document->customer)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompany(): void {
        // Prepare
        $factory = $this->app
            ->make(CustomerFactory::class)
            ->setLocationFactory($this->app->make(LocationFactory::class))
            ->setContactsFactory($this->app->make(ContactFactory::class));

        // Test
        $file     = $this->faker->randomElement(['~customer-full.json', '~reseller.json']);
        $json     = $this->getTestData()->json($file);
        $company  = new Company($json);
        $customer = $factory->create($company);

        $this->assertNotNull($customer);
        $this->assertTrue($customer->wasRecentlyCreated);
        $this->assertEquals($company->id, $customer->getKey());
        $this->assertEquals($company->name, $customer->name);
        $this->assertEquals($this->getCompanyType($company), $customer->type->key);
        $this->assertCount(2, $customer->locations);
        $this->assertEquals(2, $customer->locations_count);
        $this->assertEquals(
            $this->getCompanyLocations($company),
            $this->getCustomerLocations($customer),
        );
        $this->assertCount(4, $customer->contacts);
        $this->assertEquals(4, $customer->contacts_count);
        $this->assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($customer),
        );

        // Customer should be updated
        $json    = $this->getTestData()->json('~customer-changed.json');
        $company = new Company($json);
        $updated = $factory->create($company);

        $this->assertNotNull($updated);
        $this->assertSame($customer, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertEquals($this->getCompanyType($company), $updated->type->key);
        $this->assertCount(1, $updated->locations);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getCustomerLocations($updated),
        );
        $this->assertCount(1, $updated->contacts);
        $this->assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($updated),
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
        $company  = new Company($json);
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

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a          = new Company([
            'id' => $this->faker->uuid,
        ]);
        $b          = new Company([
            'id' => $this->faker->uuid,
        ]);
        $resolver   = $this->app->make(CustomerResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, CustomerResolver $resolver) {
                $this->normalizer = $normalizer;
                $this->customers  = $resolver;
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch(
            [
                new Asset(['customerId' => $a->id]),
                new Asset(['customerId' => $b->id]),
            ],
            false,
            Closure::fromCallable($callback),
        );

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find($a);
        $factory->find($b);

        $this->assertCount(0, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            AssetDocumentObject::class => ['createFromAssetDocumentObject', new AssetDocumentObject()],
            AssetDocument::class       => ['createFromAssetDocument', new AssetDocument()],
            Document::class            => ['createFromDocument', new Document()],
            Company::class             => ['createFromCompany', new Company()],
            'Unknown'                  => [
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
