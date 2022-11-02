<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Selectors\Asterisk;
use App\Http\Controllers\Export\Selectors\Concat;
use App\Http\Controllers\Export\Selectors\Group;
use App\Http\Controllers\Export\Selectors\LogicalOr;
use App\Http\Controllers\Export\Selectors\Property;
use App\Http\Controllers\Export\Selectors\Root;
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
            'a.b.c',
            'concat(a, a.b, or(abc, a))',
            'a.d',
            'b.*.c.d',
            'c.*',
            'd.*.e',
        ]);
        $expected = new Root([
            new Property('a', 0),
            new Group('a', [
                new Property('b', 1),
                new Group('b', [
                    new Property('c', 2),
                ]),
                new Property('d', 4),
            ]),
            new Concat(
                [
                    new Property('a', 0),
                    new Group('a', [
                        new Property('b', 0),
                    ]),
                    new LogicalOr([
                        new Property('abc', 0),
                        new Property('a', 0),
                    ], 0),
                ],
                3,
            ),
            new Group('b', [
                new Asterisk(
                    new Group('c', [
                        new Property('d', 0),
                    ]),
                    5,
                ),
            ]),
            new Group('c', [
                // empty,
            ]),
            new Group('d', [
                new Asterisk(
                    new Property('e', 0),
                    7,
                ),
            ]),
        ]);

        self::assertEquals($expected, $actual);
    }
}
