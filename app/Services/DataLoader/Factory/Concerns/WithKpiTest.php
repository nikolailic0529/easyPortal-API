<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Models\Kpi;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\CompanyKpis;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithKpi
 */
class WithKpiTest extends TestCase {
    /**
     * @covers ::kpi
     */
    public function testKpiNull(): void {
        $factory  = new WithKpiTest_Factory($this->app->make(Normalizer::class));
        $owner    = Customer::factory()->make();
        $kpis     = null;
        $actual   = $factory->kpi($owner, $kpis);
        $expected = null;

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::kpi
     */
    public function testKpi(): void {
        $factory  = new WithKpiTest_Factory($this->app->make(Normalizer::class));
        $owner    = Customer::factory()->make();
        $kpis     = new CompanyKpis([
            'totalAssets' => $this->faker->randomNumber(),
        ]);
        $actual   = $factory->kpi($owner, $kpis);
        $expected = [
            'assets_total' => (int) $kpis->totalAssets,
        ];

        $this->assertEquals($expected, [
            'assets_total' => $actual->assets_total,
        ]);
        $this->assertTrue($actual->wasRecentlyCreated);
    }

    /**
     * @covers ::kpi
     */
    public function testKpiUpdated(): void {
        $factory  = new WithKpiTest_Factory($this->app->make(Normalizer::class));
        $kpi      = Kpi::factory()->create([
            'assets_total' => 0,
        ]);
        $owner    = Customer::factory()->make()->setRelation('kpi', $kpi);
        $kpis     = new CompanyKpis([
            'totalAssets' => $this->faker->randomNumber(),
        ]);
        $actual   = $factory->kpi($owner, $kpis);
        $expected = [
            'assets_total' => (int) $kpis->totalAssets,
        ];

        $this->assertSame($kpi, $actual);
        $this->assertEquals($expected, [
            'assets_total' => $actual->assets_total,
        ]);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class WithKpiTest_Factory extends ModelFactory {
    use WithKpi {
        kpi as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    protected function getNormalizer(): Normalizer {
        return $this->normalizer;
    }

    public function create(Type $type): ?Model {
        return null;
    }
}
