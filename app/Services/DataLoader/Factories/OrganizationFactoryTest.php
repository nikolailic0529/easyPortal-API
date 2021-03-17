<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OrganizationResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\OrganizationFactory
 */
class OrganizationFactoryTest extends TestCase {
    use WithQueryLog;
    use Helper;

    // <editor-fold desc='Tests'>
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $company = Company::create([
            'id'           => $this->faker->uuid,
            'name'         => $this->faker->company,
            'companyTypes' => [['type' => 'RESELLER']],
        ]);
        $factory = $this->app->make(OrganizationFactory::class);

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
        $factory = Mockery::mock(OrganizationFactory::class);
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
        // Prepare
        $factory = $this->app
            ->make(OrganizationFactory::class)
            ->setLocationFactory($this->app->make(LocationFactory::class));

        // Test
        $json         = $this->getTestData()->json('~reseller-full.json');
        $company      = Company::create($json);
        $organization = $factory->create($company);

        $this->assertNotNull($organization);
        $this->assertTrue($organization->wasRecentlyCreated);
        $this->assertEquals($company->id, $organization->getKey());
        $this->assertEquals($company->name, $organization->name);
        $this->assertCount(2, $organization->locations);
        $this->assertEquals(2, $organization->locations_count);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getOrganizationLocations($organization),
        );

        // Customer should be updated
        $json    = $this->getTestData()->json('~reseller-changed.json');
        $company = Company::create($json);
        $updated = $factory->create($company);

        $this->assertNotNull($updated);
        $this->assertSame($organization, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertCount(1, $updated->locations);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getOrganizationLocations($updated),
        );
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyResellerOnly(): void {
        // Prepare
        $factory = $this->app->make(OrganizationFactory::class);

        // Test
        $json         = $this->getTestData()->json('~reseller-only.json');
        $company      = Company::create($json);
        $organization = $factory->create($company);

        $this->assertNotNull($organization);
        $this->assertTrue($organization->wasRecentlyCreated);
        $this->assertEquals($company->id, $organization->getKey());
        $this->assertEquals($company->name, $organization->name);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyTypeIsCustomer(): void {
        $factory = $this->app->make(OrganizationFactory::class);
        $json    = $this->getTestData()->json('~customer.json');
        $company = Company::create($json);

        $this->assertNotNull($factory->create($company));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a          = Company::create([
            'id' => $this->faker->uuid,
        ]);
        $b          = Company::create([
            'id' => $this->faker->uuid,
        ]);
        $resolver   = $this->app->make(OrganizationResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends OrganizationFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, OrganizationResolver $resolver) {
                $this->normalizer    = $normalizer;
                $this->organizations = $resolver;
            }
        };

        $factory->prefetch([$a, $b]);

        $this->flushQueryLog();

        $factory->find($a);
        $factory->find($b);

        $this->assertCount(0, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc='DataProviders'>
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
    // </editor-fold>
}
