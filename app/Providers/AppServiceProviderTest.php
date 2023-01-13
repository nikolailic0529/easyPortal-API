<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Logger\Logger;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use ReflectionClass;
use Tests\Helpers\Models;
use Tests\TestCase;

use function array_keys;
use function implode;
use function ksort;

use const PHP_EOL;

/**
 * @internal
 * @covers \App\Providers\AppServiceProvider
 */
class AppServiceProviderTest extends TestCase {
    public function testRegister(): void {
        // All dates must be immutable
        self::assertInstanceOf(CarbonImmutable::class, Date::now());
        self::assertInstanceOf(DateTimeImmutable::class, Date::now());

        // Serialization should use ISO 8601
        $model = new class() extends Model {
            // empty
        };

        $model->setAttribute('id', $this->faker->uuid());
        $model->setAttribute('updated_at', Date::make('2102-12-01T22:12:01.000+00:00'));

        self::assertEquals([
            'id'         => $model->getKey(),
            'updated_at' => '2102-12-01T22:12:01+00:00',
        ], $model->toArray());
    }

    public function testBootMorphMap(): void {
        $expected = [];
        $actual   = Relation::$morphMap;
        $models   = Models::get()
            ->filter(static function (ReflectionClass $class): bool {
                return !$class->isAbstract()
                    && $class->newInstance()->getConnectionName() !== Logger::CONNECTION;
            });

        foreach ($models as $model) {
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
