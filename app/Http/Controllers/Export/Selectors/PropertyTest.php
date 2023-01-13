<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Selectors\Property
 */
class PropertyTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderFill
     *
     * @param array<mixed>                          $expected
     * @param int<0, max>                           $index
     * @param array<scalar|null|array<scalar|null>> $item
     */
    public function testFill(array $expected, string $property, int $index, array $item): void {
        $row      = [];
        $selector = new Property($property, $index);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
    }

    public function testGetSelectors(): void {
        $selector = new Property('b', 1);
        $expected = [
            'b',
        ];

        self::assertEquals($expected, $selector->getSelectors());
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{array<mixed>, string, int<0, max>, array<scalar|null|array<scalar|null>>}>
     */
    public function dataProviderFill(): array {
        return [
            'property' => [
                [
                    2 => 123,
                ],
                'property',
                2,
                [
                    'property' => 123,
                ],
            ],
            'unknown'  => [
                [
                    1 => null,
                ],
                'unknown',
                1,
                [
                    'property' => 123,
                ],
            ],
            'json'     => [
                [
                    4 => '{"a":"value-a"}',
                ],
                'property',
                4,
                [
                    'property' => [
                        'a' => 'value-a',
                    ],
                ],
            ],
        ];
    }
    // </editor-fold>
}
