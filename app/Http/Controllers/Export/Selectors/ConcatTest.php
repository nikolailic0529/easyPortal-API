<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Selectors\Concat
 */
class ConcatTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderFill
     *
     * @param array<mixed>                          $expected
     * @param int<0, max>                           $index
     * @param non-empty-array<Selector>             $arguments
     * @param array<scalar|null|array<scalar|null>> $item
     */
    public function testFill(array $expected, int $index, array $arguments, array $item): void {
        $row      = [];
        $selector = new Concat($arguments, $index);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
    }

    public function testGetSelectors(): void {
        $selector = new Concat(
            [
                new Asterisk(
                    new Group('a', [
                        new Property('b', 1),
                        new Group('b', [
                            new Property('c', 2),
                        ]),
                        new Property('d', 4),
                    ]),
                    0,
                ),
                new Property('a', 4),
                new Property('a', 4),
            ],
            0,
        );
        $expected = [
            '*.a.b',
            '*.a.b.c',
            '*.a.d',
            'a',
        ];

        self::assertEquals($expected, $selector->getSelectors());
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{
     *     array<mixed>,
     *     int<0, max>,
     *     non-empty-array<Selector>,
     *     array<scalar|null|array<scalar|null>>
     *     }>
     */
    public function dataProviderFill(): array {
        return [
            'concat' => [
                [
                    1 => '123 45',
                ],
                1,
                [
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            $row[1] = $item[0];
                        }

                        /**
                         * @inheritdoc
                         */
                        public function getSelectors(): array {
                            return [];
                        }
                    },
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            $row[5] = $item[1];
                        }

                        /**
                         * @inheritdoc
                         */
                        public function getSelectors(): array {
                            return [];
                        }
                    },
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            $row[6] = $item[2];
                        }

                        /**
                         * @inheritdoc
                         */
                        public function getSelectors(): array {
                            return [];
                        }
                    },
                ],
                [
                    '123',
                    null,
                    45,
                ],
            ],
        ];
    }
    // </editor-fold>
}
