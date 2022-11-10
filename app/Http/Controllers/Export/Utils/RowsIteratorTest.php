<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Utils\Iterators\ObjectsIterator;
use Tests\TestCase;

use function array_map;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\Utils\RowsIterator
 */
class RowsIteratorTest extends TestCase {
    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $headers  = 0;//max(0, $this->faker->randomDigit());
        $items    = [
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
        $expected = [
            [
                'index'  => 0 + $headers,
                'level'  => 1,
                'item'   => ['AA', 'BA', 1],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 1 + $headers,
                'level'  => 2,
                'item'   => ['AA', 'BB', 2],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 2 + $headers,
                'level'  => 2,
                'item'   => ['AA', 'BB', 3],
                'groups' => [
                    0 => [
                        'start' => 0 + $headers,
                        'end'   => 2 + $headers,
                    ],
                    1 => [
                        'start' => 1 + $headers,
                        'end'   => 2 + $headers,
                    ],
                ],
            ],
            [
                'index'  => 3 + $headers,
                'level'  => 2,
                'item'   => ['AB', 'BA', 4],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 4 + $headers,
                'level'  => 2,
                'item'   => ['AB', 'BA', 5],
                'groups' => [
                    1 => [
                        'start' => 5 + $headers,
                        'end'   => 6 + $headers,
                    ],
                ],
            ],
            [
                'index'  => 5 + $headers,
                'level'  => 2,
                'item'   => ['AB', 'BB', 6],
                'groups' => [
                    // empty
                ],
            ],
            [
                'index'  => 6 + $headers,
                'level'  => 2,
                'item'   => ['AB', 'BB', 7],
                'groups' => [
                    1 => [
                        'start' => 8 + $headers,
                        'end'   => 9 + $headers,
                    ],
                ],
            ],
            [
                'index'  => 7 + $headers,
                'level'  => 1,
                'item'   => ['AB', 'BC', 8],
                'groups' => [
                    0 => [
                        'start' => 5 + $headers,
                        'end'   => 11 + $headers,
                    ],
                ],
            ],
            [
                'index'  => 8 + $headers,
                'level'  => 0,
                'item'   => ['AC', 'BC', 9],
                'groups' => [
                    // empty
                ],
            ],
        ];
        $iterator = new RowsIterator(
            new ObjectsIterator($items),
            SelectorFactory::make(['a', 'b', 'id']),
            SelectorFactory::make(['a', 'b']),
            [
                new Group(),
                new Group(),
            ],
            [
                null,
                null,
                null,
            ],
            $headers,
        );
        $actual   = [];

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

        self::assertEquals($expected, $actual);
    }
}
