<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\Export\Selectors\Value
 */
class ValueTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::fill
     *
     * @dataProvider dataProviderFill
     *
     * @param array<mixed>                          $expected
     * @param array<scalar|null|array<scalar|null>> $item
     */
    public function testFill(array $expected, string $property, int $index, array $item): void {
        $row      = [];
        $selector = new Value($property, $index);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{array<mixed>, string, int, array<scalar|null|array<scalar|null>>}>
     */
    public function dataProviderFill(): array {
        return [
            'property' => [
                [
                    2 => '123',
                ],
                'property',
                2,
                [
                    'property' => 123,
                ],
            ],
            'unknown'  => [
                [
                    1 => '',
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
