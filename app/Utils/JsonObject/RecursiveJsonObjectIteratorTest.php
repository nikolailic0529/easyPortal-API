<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use RecursiveIteratorIterator;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Utils\JsonObject\RecursiveJsonObjectIterator
 */
class RecursiveJsonObjectIteratorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderIterate
     *
     * @param array<mixed>                                         $expected
     * @param JsonObject|array<JsonObject>|array<array-key, mixed> $items
     */
    public function testIterate(array $expected, JsonObject|array $items): void {
        $actual   = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveJsonObjectIterator($items),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $key => $value) {
            $actual[] = [
                'key'   => $key,
                'value' => $value,
            ];
        }

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return non-empty-array<string, array{array<mixed>, JsonObject|array<JsonObject>|array<array-key, mixed>}>
     */
    public function dataProviderIterate(): array {
        $a = new RecursiveJsonObjectIteratorTest_Object([
            'value' => 'value-a',
        ]);
        $b = new RecursiveJsonObjectIteratorTest_Object([
            'value'    => 'value-b',
            'children' => [
                $a,
            ],
        ]);
        $c = new class($a) extends stdClass {
            public function __construct(
                public mixed $c,
            ) {
                // empty
            }
        };

        return [
            'mixed'             => [
                [
                    [
                        'key'   => 0,
                        'value' => 'a',
                    ],
                    [
                        'key'   => 1,
                        'value' => $a,
                    ],
                    [
                        'key'   => 'value',
                        'value' => 'value-a',
                    ],
                ],
                [
                    'a',
                    $a,
                ],
            ],
            'array<mixed>'      => [
                [
                    [
                        'key'   => 0,
                        'value' => ['a', $a],
                    ],
                    [
                        'key'   => 0,
                        'value' => 'a',
                    ],
                    [
                        'key'   => 1,
                        'value' => $a,
                    ],
                    [
                        'key'   => 'value',
                        'value' => 'value-a',
                    ],
                ],
                [
                    ['a', $a],
                ],
            ],
            'array<object>'     => [
                [
                    [
                        'key'   => 0,
                        'value' => $c,
                    ],
                ],
                [
                    $c,
                ],
            ],
            'JsonObject'        => [
                [
                    [
                        'key'   => 0,
                        'value' => $b,
                    ],
                    [
                        'key'   => 'value',
                        'value' => 'value-b',
                    ],
                    [
                        'key'   => 'children',
                        'value' => [$a],
                    ],
                    [
                        'key'   => 0,
                        'value' => $a,
                    ],
                    [
                        'key'   => 'value',
                        'value' => 'value-a',
                    ],
                ],
                $b,
            ],
            'array<JsonObject>' => [
                [
                    [
                        'key'   => 0,
                        'value' => $b,
                    ],
                    [
                        'key'   => 'value',
                        'value' => 'value-b',
                    ],
                    [
                        'key'   => 'children',
                        'value' => [$a],
                    ],
                    [
                        'key'   => 0,
                        'value' => $a,
                    ],
                    [
                        'key'   => 'value',
                        'value' => 'value-a',
                    ],
                ],
                [$b],
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @property string $id
 */
class RecursiveJsonObjectIteratorTest_Object extends JsonObject {
    public string $value;

    /**
     * @var array<RecursiveJsonObjectIteratorTest_Object>
     */
    #[JsonObjectArray(RecursiveJsonObjectIteratorTest_Object::class)]
    public array $children;
}
