<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\Model;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Tests\Helpers\Models;
use Tests\TestCase;

use function ksort;

/**
 * @internal
 * @coversDefaultClass \App\Providers\AppServiceProvider
 */
class AppServiceProviderTest extends TestCase {
    /**
     * @covers ::register
     */
    public function testRegister(): void {
        // All dates must be immutable
        $this->assertInstanceOf(CarbonImmutable::class, Date::now());
        $this->assertInstanceOf(DateTimeImmutable::class, Date::now());

        // Serialization should use ISO 8601
        $model = new class() extends Model {
            // empty
        };

        $model->id         = $this->faker->uuid;
        $model->updated_at = Date::make('2102-12-01T22:12:01.000+00:00');

        $this->assertEquals([
            'id'         => $model->getKey(),
            'updated_at' => '2102-12-01T22:12:01+00:00',
        ], $model->toArray());
    }

    /**
     * @covers ::bootMorphMap
     */
    public function testBootMorphMap(): void {
        $expected = [];
        $actual   = Relation::$morphMap;

        foreach (Models::get() as $model) {
            $expected[$model->getShortName()] = $model->getName();
        }

        ksort($expected);

        $this->assertEquals($actual, $expected);
    }
}
