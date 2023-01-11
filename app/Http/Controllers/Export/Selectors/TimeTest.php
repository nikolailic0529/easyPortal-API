<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Selectors;

use App\Http\Controllers\Export\Selector;
use App\Services\I18n\Formatter;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Selectors\Time
 */
class TimeTest extends TestCase {
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
        $row       = [];
        $formatter = $this->app->make(Formatter::class);
        $selector  = new Time($formatter, $arguments, $index);

        $selector->fill($item, $row);

        self::assertEquals($expected, $row);
    }

    public function testGetSelectors(): void {
        $formatter = $this->app->make(Formatter::class);
        $selector  = new Time(
            $formatter,
            [
                new Property('a', 4),
            ],
            0,
        );
        $expected  = [
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
        $selectors = [
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
        ];

        return [
            'time' => [
                [
                    2 => '08:22:00',
                ],
                2,
                $selectors,
                [
                    '2022-11-14T08:22:00',
                ],
            ],
            'null' => [
                [
                    3 => '',
                ],
                3,
                $selectors,
                [
                    null,
                ],
            ],
        ];
    }
    // </editor-fold>
}
