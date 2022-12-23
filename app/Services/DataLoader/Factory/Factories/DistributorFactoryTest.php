<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Distributor;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

use function array_column;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Factories\DistributorFactory
 */
class DistributorFactoryTest extends TestCase {
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
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
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompany(): void {
        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');

        // Prepare
        $factory = $this->app->make(DistributorFactory::class);

        // Load
        $json    = $this->getTestData()->json('~distributor-full.json');
        $company = new Company($json);

        // Test
        $queries     = $this->getQueryLog()->flush();
        $distributor = $factory->create($company);
        $actual      = array_column($queries->get(), 'query');
        $expected    = $this->getTestData()->json('~createFromCompany-create-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($distributor);
        self::assertTrue($distributor->wasRecentlyCreated);
        self::assertEquals($company->id, $distributor->getKey());
        self::assertEquals($company->name, $distributor->name);
        self::assertEquals($company->updatedAt, $this->getDatetime($distributor->changed_at));

        // Distributor should be updated
        $json     = $this->getTestData()->json('~distributor-changed.json');
        $company  = new Company($json);
        $queries  = $this->getQueryLog()->flush();
        $updated  = $factory->create($company);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-update-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($updated);
        self::assertSame($distributor, $updated);
        self::assertEquals($company->id, $updated->getKey());
        self::assertEquals($company->name, $updated->name);
        self::assertEquals($company->updatedAt, $this->getDatetime($updated->changed_at));

        // No changes
        $json    = $this->getTestData()->json('~distributor-changed.json');
        $company = new Company($json);
        $queries = $this->getQueryLog()->flush();

        $factory->create($company);

        self::assertCount(0, $queries);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyTrashed(): void {
        // Prepare
        $factory = $this->app->make(DistributorFactory::class);
        $json    = $this->getTestData()->json('~distributor-full.json');
        $company = new Company($json);
        $model   = Distributor::factory()->create([
            'id' => $company->id,
        ]);

        self::assertTrue($model->delete());
        self::assertTrue($model->trashed());

        // Test
        $created = $factory->create($company);

        self::assertNotNull($created);
        self::assertFalse($created->trashed());
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
    // </editor-fold>
}
