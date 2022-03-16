<?php declare(strict_types = 1);

namespace App\Providers;

use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Tests\Helpers\Models;
use Tests\TestCase;

use function array_keys;
use function implode;
use function ksort;

use const PHP_EOL;

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
        self::assertInstanceOf(CarbonImmutable::class, Date::now());
        self::assertInstanceOf(DateTimeImmutable::class, Date::now());

        // Serialization should use ISO 8601
        $model = new class() extends Model {
            // empty
        };

        $model->id         = $this->faker->uuid;
        $model->updated_at = Date::make('2102-12-01T22:12:01.000+00:00');

        self::assertEquals([
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

        self::assertEquals($actual, $expected, 'Map is not actual.');
        self::assertEquals(
            implode(PHP_EOL, array_keys($actual)),
            implode(PHP_EOL, array_keys($expected)),
            'Map is not sorted alphabetically.',
        );
    }
}
