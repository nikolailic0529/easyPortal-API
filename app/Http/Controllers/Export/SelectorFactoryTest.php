<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Selectors\Asterisk;
use App\Http\Controllers\Export\Selectors\Concat;
use App\Http\Controllers\Export\Selectors\Group;
use App\Http\Controllers\Export\Selectors\LogicalOr;
use App\Http\Controllers\Export\Selectors\Root;
use App\Http\Controllers\Export\Selectors\Value;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\SelectorFactory
 */
class SelectorFactoryTest extends TestCase {
    /**
     * @covers ::make
     */
    public function testMake(): void {
        $actual   = SelectorFactory::make([
            'a',
            'a.b',
            'concat(a, a.b, or(abc, a))',
            'a.d',
            'b.*.c.d',
            'c.*',
        ]);
        $expected = new Root([
            new Value('a', 0),
            new Group('a', [
                new Value('b', 1),
                new Value('d', 3),
            ]),
            new Concat(
                [
                    new Value('a', 0),
                    new Group('a', [
                        new Value('b', 0),
                    ]),
                    new LogicalOr([
                        new Value('abc', 0),
                        new Value('a', 0),
                    ], 0),
                ],
                2,
            ),
            new Group('b', [
                new Asterisk('c.d', 4),
            ]),
            new Group('c', [
                // empty,
            ]),
        ]);

        self::assertEquals($expected, $actual);
    }
}
