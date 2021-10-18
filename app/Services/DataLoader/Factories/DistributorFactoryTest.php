<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

use function array_column;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\DistributorFactory
 */
class DistributorFactoryTest extends TestCase {
    use WithQueryLog;
    use Helper;

    // <editor-fold desc='Tests'>
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(DistributorFactory::class);
        $json    = $this->getTestData()->json('~distributor-full.json');
        $company = new Company($json);

        $this->flushQueryLog();

        $factory->find($company);

        $this->assertCount(2, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(DistributorFactory::class);
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
        // Mock
        $this->overrideDateFactory();

        // Prepare
        $factory = $this->app->make(DistributorFactory::class);

        // Load
        $json    = $this->getTestData()->json('~distributor-full.json');
        $company = new Company($json);

        $this->flushQueryLog();

        // Test
        $distributor = $factory->create($company);
        $actual      = array_column($this->getQueryLog(), 'query');
        $expected    = $this->getTestData()->json('~createFromCompany-create-expected.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($distributor);
        $this->assertTrue($distributor->wasRecentlyCreated);
        $this->assertEquals($company->id, $distributor->getKey());
        $this->assertEquals($company->name, $distributor->name);
        $this->assertEquals($company->updatedAt, $this->getDatetime($distributor->changed_at));

        $this->flushQueryLog();

        // Distributor should be updated
        $json     = $this->getTestData()->json('~distributor-changed.json');
        $company  = new Company($json);
        $updated  = $factory->create($company);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-update-expected.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($updated);
        $this->assertSame($distributor, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertEquals($company->updatedAt, $this->getDatetime($updated->changed_at));

        $this->flushQueryLog();

        // No changes
        $json    = $this->getTestData()->json('~distributor-changed.json');
        $company = new Company($json);

        $factory->create($company);

        $this->assertCount(1, $this->getQueryLog());
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
        $resolver   = $this->app->make(DistributorResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends DistributorFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected DistributorResolver $distributorResolver,
            ) {
                // empty
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch([$a, $b], false, Closure::fromCallable($callback));

        $callback->shouldHaveBeenCalled()->once();

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
