<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\ResellerFactory
 */
class ResellerFactoryTest extends TestCase {
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
        $factory = $this->app->make(ResellerFactory::class);

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
        $factory = Mockery::mock(ResellerFactory::class);
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
            ->make(ResellerFactory::class)
            ->setLocationFactory($this->app->make(LocationFactory::class));

        // Test
        $json     = $this->getTestData()->json('~reseller-full.json');
        $company  = Company::create($json);
        $reseller = $factory->create($company);

        $this->assertNotNull($reseller);
        $this->assertTrue($reseller->wasRecentlyCreated);
        $this->assertEquals($company->id, $reseller->getKey());
        $this->assertEquals($company->name, $reseller->name);
        $this->assertCount(2, $reseller->locations);
        $this->assertEquals(2, $reseller->locations_count);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getResellerLocations($reseller),
        );

        // Customer should be updated
        $json    = $this->getTestData()->json('~reseller-changed.json');
        $company = Company::create($json);
        $updated = $factory->create($company);

        $this->assertNotNull($updated);
        $this->assertSame($reseller, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertCount(1, $updated->locations);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getResellerLocations($updated),
        );
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyResellerOnly(): void {
        // Prepare
        $factory = $this->app->make(ResellerFactory::class);

        // Test
        $json     = $this->getTestData()->json('~reseller-only.json');
        $company  = Company::create($json);
        $reseller = $factory->create($company);

        $this->assertNotNull($reseller);
        $this->assertTrue($reseller->wasRecentlyCreated);
        $this->assertEquals($company->id, $reseller->getKey());
        $this->assertEquals($company->name, $reseller->name);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyTypeIsCustomer(): void {
        $factory = $this->app->make(ResellerFactory::class);
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
        $resolver   = $this->app->make(ResellerResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends ResellerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, ResellerResolver $resolver) {
                $this->normalizer = $normalizer;
                $this->resellers  = $resolver;
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
