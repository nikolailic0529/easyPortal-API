<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\Selectors\Group
 */
class GroupTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::fill
     *
     * @dataProvider dataProviderFill
     *
     * @param array<mixed>                          $expected
     * @param array<Selector>                       $selectors
     * @param array<scalar|null|array<scalar|null>> $item
     */
    public function testFill(array $expected, string $property, array $selectors, array $item): void {
        $row      = [];
        $selector = new Group($property, $selectors);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{array<mixed>, string, array<Selector>, array<scalar|null|array<scalar|null>>}>
     */
    public function dataProviderFill(): array {
        return [
            'property'  => [
                [
                    1 => '123',
                    5 => '45',
                ],
                'property',
                [
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            $row[1] = $item[0];
                        }
                    },
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            $row[5] = $item[1];
                        }
                    },
                ],
                [
                    'property' => [
                        0 => '123',
                        1 => '45',
                    ],
                ],
            ],
            'unknown'   => [
                [
                    // empty
                ],
                'property',
                [
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            // empty
                        }
                    },
                ],
                [
                    'unknown' => [
                        0 => '123',
                        1 => '45',
                    ],
                ],
            ],
            'not array' => [
                [
                    // empty
                ],
                'property',
                [
                    new class() implements Selector {
                        /**
                         * @inheritdoc
                         */
                        public function fill(array $item, array &$row): void {
                            // empty
                        }
                    },
                ],
                [
                    'property' => 'not array',
                ],
            ],
        ];
    }
    // </editor-fold>
}
