<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Selectors\Root
 */
class RootTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderFill
     *
     * @param array<mixed>                          $expected
     * @param array<Selector>                       $selectors
     * @param array<scalar|null|array<scalar|null>> $item
     */
    public function testFill(array $expected, array $selectors, array $item): void {
        $row      = [];
        $selector = new Root($selectors);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
        self::assertEquals($expected, $selector->get($item));
    }

    public function testGetSelectors(): void {
        $asterisk = new Asterisk(
            new Group('a', [
                new Property('b', 1),
                new Group('b', [
                    new Property('c', 2),
                ]),
                new Property('d', 4),
            ]),
            0,
        );
        $selector = new Root([
            new Group('a', [
                $asterisk,
            ]),
            new Concat(
                [
                    $asterisk,
                    new Property('a', 4),
                    new Property('a', 4),
                ],
                0,
            ),
            $asterisk,
        ]);
        $expected = [
            'a.*.a.b',
            'a.*.a.b.c',
            'a.*.a.d',
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
     * @return array<string, array{array<mixed>, non-empty-array<Selector>, array<scalar|null|array<scalar|null>>}>
     */
    public function dataProviderFill(): array {
        return [
            'fill' => [
                [
                    1 => '123',
                    5 => null,
                ],
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
                ],
                [
                    '123',
                    null,
                ],
            ],
        ];
    }
    // </editor-fold>
}
