<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Utils\Measurer
 */
class MeasurerTest extends TestCase {
    public function testMeasure(): void {
        $measurer = new Measurer();

        self::assertEquals(
            [
                // empty
            ],
            $measurer
                ->measure([
                    0 => '',
                    1 => null,
                ])
                ->getColumns(),
        );
        self::assertEquals(
            [
                0 => 1,
            ],
            $measurer
                ->measure([
                    0 => 'a',
                ])
                ->getColumns(),
        );
        self::assertEquals(
            [
                0 => 2,
            ],
            $measurer
                ->measure([
                    0 => 'aa',
                ])
                ->getColumns(),
        );
        self::assertEquals(
            [
                0 => 2,
            ],
            $measurer
                ->measure([
                    0 => 'a',
                ])
                ->getColumns(),
        );
        self::assertEquals(
            [
                0 => 5,
                1 => 3,
            ],
            $measurer
                ->measure([
                    0 => "12345\n12345678",
                    1 => '123',
                ])
                ->getColumns(),
        );
    }
}
