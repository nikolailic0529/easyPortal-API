<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Services\I18n\Formatter;
use App\Utils\Iterators\ObjectsIterator;
use Tests\TestCase;

use function array_map;
use function count;
use function max;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Utils\RowsIterator
 */
class RowsIteratorTest extends TestCase {
    public function testGetIterator(): void {
        $formatter = $this->app->make(Formatter::class);
        $offset    = $this->faker->randomDigit();
        $offset    = max($offset, 0);
        $items     = [
            ['a' => 'AA', 'b' => 'BA', 'id' => 1],
            ['a' => 'AA', 'b' => 'BB', 'id' => 2],
            ['a' => 'AA', 'b' => 'BB', 'id' => 3],
            ['a' => 'AB', 'b' => 'BA', 'id' => 4],
            ['a' => 'AB', 'b' => 'BA', 'id' => 5],
            ['a' => 'AB', 'b' => 'BB', 'id' => 6],
            ['a' => 'AB', 'b' => 'BB', 'id' => 7],
            ['a' => 'AB', 'b' => 'BC', 'id' => 8],
            ['a' => 'AC', 'b' => 'BC', 'id' => 9],
        ];
        $expected  = [
            [
                'index'  => 0,
                'level'  => 1,
                'item'   => ['AA', 'BA', 1],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 1,
                'level'  => 2,
                'item'   => ['AA', 'BB', 2],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 2,
                'level'  => 2,
                'item'   => ['AA', 'BB', 3],
                'groups' => [
                    0 => [
                        'start' => 0 + $offset,
                        'end'   => 2 + $offset,
                    ],
                    1 => [
                        'start' => 1 + $offset,
                        'end'   => 2 + $offset,
                    ],
                ],
            ],
            [
                'index'  => 3,
                'level'  => 2,
                'item'   => ['AB', 'BA', 4],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 4,
                'level'  => 2,
                'item'   => ['AB', 'BA', 5],
                'groups' => [
                    1 => [
                        'start' => 5 + $offset,
                        'end'   => 6 + $offset,
                    ],
                ],
            ],
            [
                'index'  => 5,
                'level'  => 2,
                'item'   => ['AB', 'BB', 6],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 6,
                'level'  => 2,
                'item'   => ['AB', 'BB', 7],
                'groups' => [
                    1 => [
                        'start' => 8 + $offset,
                        'end'   => 9 + $offset,
                    ],
                ],
            ],
            [
                'index'  => 7,
                'level'  => 1,
                'item'   => ['AB', 'BC', 8],
                'groups' => [
                    0 => [
                        'start' => 5 + $offset,
                        'end'   => 11 + $offset,
                    ],
                ],
            ],
            [
                'index'  => 8,
                'level'  => 0,
                'item'   => ['AC', 'BC', 9],
                'groups' => [
                    // empty
                ],
            ],
        ];
        $iterator = new RowsIterator(
            new ObjectsIterator($items),
            SelectorFactory::make($formatter, ['a', 'b', 'id']),
            SelectorFactory::make($formatter, ['a', 'b']),
            [
                new Group(),
                new Group(),
            ],
            [
                null,
                null,
                null,
            ],
            $offset,
        );
        $actual   = $this->getItems($iterator);
        $second   = $this->getItems($iterator);

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $second);
        self::assertEquals($offset, $iterator->getOffset());
        self::assertEquals([], $iterator->getGroups());
    }

    /**
     * @return array<mixed>
     */
    private function getItems(RowsIterator $iterator): array {
        $actual = [];

        foreach ($iterator as $index => $item) {
            $groups   = $iterator->getGroups();
            $actual[] = [
                'index'  => $index,
                'level'  => $iterator->getLevel(),
                'item'   => $item,
                'groups' => array_map(
                    static function (Group $group): array {
                        return [
                            'start' => $group->getStartRow(),
                            'end'   => $group->getEndRow(),
                        ];
                    },
                    $groups,
                ),
            ];

            $iterator->setOffset(count($groups) + 1);
        }

        return $actual;
    }
}
