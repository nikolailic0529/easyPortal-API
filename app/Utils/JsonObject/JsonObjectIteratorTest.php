<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Utils\JsonObject\JsonObjectIterator
 */
class JsonObjectIteratorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderGetIterator
     *
     * @param array<mixed>                              $expected
     * @param JsonObject|array<JsonObject>|array<mixed> $items
     */
    public function testGetIterator(array $expected, JsonObject|array $items): void {
        $actual   = [];
        $iterator = new JsonObjectIterator($items);

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
     * @return non-empty-array<string, array{array<mixed>, JsonObject|array<JsonObject>|array<mixed>}>
     */
    public function dataProviderGetIterator(): array {
        $a = new JsonObjectIteratorTest_Object([
            'value' => 'value-a',
        ]);
        $b = new JsonObjectIteratorTest_Object([
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
            'JsonObject'        => [
                [
                    [
                        'key'   => 0,
                        'value' => $b,
                    ],
                    [
                        'key'   => 0,
                        'value' => $a,
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
                        'key'   => 0,
                        'value' => $a,
                    ],
                ],
                [$b],
            ],
            'array<mixed>'      => [
                [
                    [
                        'key'   => 0,
                        'value' => $c,
                    ],
                ],
                [$c],
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
class JsonObjectIteratorTest_Object extends JsonObject {
    public string $value;

    /**
     * @var array<JsonObjectIteratorTest_Object>
     */
    #[JsonObjectArray(JsonObjectIteratorTest_Object::class)]
    public array $children;
}
