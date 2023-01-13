<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Selectors\Asterisk
 */
class AsteriskTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderFill
     *
     * @param array<mixed>                          $expected
     * @param int<0, max>                           $index
     * @param array<scalar|null|array<scalar|null>> $item
     */
    public function testFill(array $expected, Selector $property, int $index, array $item): void {
        $row      = [];
        $selector = new Asterisk($property, $index);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
    }

    public function testGetSelectors(): void {
        $selector = new Asterisk(
            new Group('a', [
                new Property('b', 1),
                new Group('b', [
                    new Property('c', 2),
                ]),
                new Property('d', 4),
            ]),
            0,
        );
        $expected = [
            '*.a.b',
            '*.a.b.c',
            '*.a.d',
        ];

        self::assertEquals($expected, $selector->getSelectors());
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{
     *     array<mixed>,
     *     Selector,
     *     int<0, max>,
     *     array<scalar|null|array<scalar|null|array<scalar|null|array<scalar|null>>>>
     *     }>
     */
    public function dataProviderFill(): array {
        return [
            'property' => [
                [
                    2 => null,
                ],
                new Property('property', 0),
                2,
                [
                    'property' => 123,
                ],
            ],
            'array'    => [
                [
                    1 => '1, 3',
                ],
                new Property('property', 0),
                1,
                [
                    [
                        'property' => 1,
                    ],
                    [
                        'property' => null,
                    ],
                    [
                        'property' => 3,
                    ],
                ],
            ],
            'json'     => [
                [
                    4 => '{"a":"value-a"}, {"b":"value-b"}',
                ],
                new Property('property', 0),
                4,
                [
                    [
                        'property' => [
                            'a' => 'value-a',
                        ],
                    ],
                    [
                        'property' => null,
                    ],
                    [
                        'property' => [
                            'b' => 'value-b',
                        ],
                    ],
                ],
            ],
            'nested'   => [
                [
                    1 => '1, 2, 3',
                ],
                new Group('b', [
                    new Asterisk(
                        new Property('d', 0),
                        5,
                    ),
                ]),
                1,
                [
                    [
                        'b' => [
                            [
                                'd' => '1',
                            ],
                            [
                                'd' => '2',
                            ],
                        ],
                    ],
                    [
                        'b' => [
                            [
                                'd' => '3',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
    // </editor-fold>
}
