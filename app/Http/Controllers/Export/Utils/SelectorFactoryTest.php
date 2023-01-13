<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Http\Controllers\Export\Exceptions\SelectorAsteriskPropertyUnknown;
use App\Http\Controllers\Export\Exceptions\SelectorFunctionUnknown;
use App\Http\Controllers\Export\Exceptions\SelectorSyntaxError;
use App\Http\Controllers\Export\Selectors\Asterisk;
use App\Http\Controllers\Export\Selectors\Concat;
use App\Http\Controllers\Export\Selectors\Group;
use App\Http\Controllers\Export\Selectors\LogicalOr;
use App\Http\Controllers\Export\Selectors\Property;
use App\Http\Controllers\Export\Selectors\Root;
use App\Services\I18n\Formatter;
use Exception;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Http\Controllers\Export\Utils\SelectorFactory
 */
class SelectorFactoryTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderMake
     *
     * @param array<int<0, max>, string> $selectors
     */
    public function testMake(Root|Exception $expected, array $selectors): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $formatter = $this->app->make(Formatter::class);
        $actual    = SelectorFactory::make($formatter, $selectors);

        self::assertEquals($expected, $actual);
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Root|Exception, array<int<0, max>, string>}>
     */
    public function dataProviderMake(): array {
        return [
            'valid'                     => [
                new Root([
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
                    new Group('d', [
                        new Asterisk(
                            new Property('e', 0),
                            6,
                        ),
                    ]),
                    new Group('e', [
                        new Asterisk(
                            new Group('f', [
                                new Asterisk(
                                    new Property('g', 0),
                                    0,
                                ),
                            ]),
                            7,
                        ),
                    ]),
                ]),
                [
                    'a',
                    'a.b',
                    'a.b.c',
                    'concat(a, a.b, or(abc, a))',
                    'a.d',
                    'b.*.c.d',
                    'd.*.e',
                    'e.*.f.*.g',
                ],
            ],
            'unknown function'          => [
                new SelectorFunctionUnknown('unknown'),
                [
                    'unknown(a.b)',
                ],
            ],
            'syntax error'              => [
                new SelectorSyntaxError(),
                [
                    'unknown(a.b',
                ],
            ],
            'asterisk without property' => [
                new SelectorAsteriskPropertyUnknown(),
                [
                    'a.*',
                ],
            ],
        ];
    }
    // </editor-fold>
}
