<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Utils\JsonObject\JsonObjectIterator
 */
class JsonObjectIteratorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getIterator
     *
     * @dataProvider dataProviderGetIterator
     *
     * @param array<mixed>                 $expected
     * @param JsonObject|array<JsonObject> $items
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
     * @return non-empty-array<string, array{array<mixed>, JsonObject|array<JsonObject>}>
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
