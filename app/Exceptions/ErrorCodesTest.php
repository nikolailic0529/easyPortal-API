<?php declare(strict_types = 1);

namespace App\Exceptions;

use Illuminate\Support\Collection;
use ReflectionClass;
use Tests\Helpers\ClassMap;
use Tests\TestCase;

use function array_diff;
use function array_keys;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function sprintf;

use const PHP_EOL;

/**
 * @internal
 * @coversDefaultClass \App\Exceptions\ErrorCodes
 */
class ErrorCodesTest extends TestCase {
    /**
     * @covers ::getMap
     */
    public function testMapActual(): void {
        $actual   = (new Collection(ErrorCodes::getMap()))->keys()->sort()->values()->implode(PHP_EOL);
        $expected = ClassMap::get()
            ->filter(static function (ReflectionClass $class): bool {
                return $class->isSubclassOf(TranslatedException::class)
                    && !$class->isAbstract();
            })
            ->map(static function (ReflectionClass $class): string {
                return $class->getName();
            })
            ->values()
            ->sort()
            ->implode(PHP_EOL);

        $this->assertEquals($expected, $actual, 'Map is not actual.');
    }

    /**
     * @covers ::getMap
     */
    public function testMapAllCodesUnique(): void {
        $codes   = array_values(ErrorCodes::getMap());
        $unique  = array_unique($codes);
        $invalid = [];

        if (count($unique) !== count($codes)) {
            foreach (array_diff(array_keys($codes), array_keys($unique)) as $index) {
                $invalid[] = $codes[$index];
            }
        }

        $this->assertEquals(count($unique), count($codes), sprintf(
            'Following codes used by more than one exception: `%s`.',
            implode('`, `', $invalid),
        ));
    }
}
