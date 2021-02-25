<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Utils;

use PHPUnit\Framework\TestCase;

use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Utils\JsonFactory
 */
class JsonFactoryTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $expected = tap(new JsonFactoryTest_Parent(), static function (JsonFactoryTest_Parent $parent): void {
            $parent->i        = 123;
            $parent->b        = true;
            $parent->f        = 1.2;
            $parent->s        = '123';
            $parent->array    = [
                'i' => 123,
                'b' => true,
                'f' => 1.2,
                's' => '123',
            ];
            $parent->children = [
                tap(new JsonFactoryTest_Child(), static function (JsonFactoryTest_Child $child): void {
                    $child->i        = 345;
                    $child->b        = false;
                    $child->f        = 3.5;
                    $child->s        = '345';
                    $child->children = [
                        tap(new JsonFactoryTest_Child(), static function (JsonFactoryTest_Child $child): void {
                            $child->i = 345;
                            $child->b = false;
                            $child->f = 3.5;
                            $child->s = '345';
                        }),
                    ];
                }),
                tap(new JsonFactoryTest_Child(), static function (JsonFactoryTest_Child $child): void {
                    $child->i        = 567;
                    $child->b        = true;
                    $child->f        = 5.7;
                    $child->s        = '567';
                    $child->children = [];
                }),
            ];
        });
        $actual   = JsonFactoryTest_Parent::create([
            'i'        => 123,
            'b'        => true,
            'f'        => 1.2,
            's'        => '123',
            'array'    => [
                'i' => 123,
                'b' => true,
                'f' => 1.2,
                's' => '123',
            ],
            'children' => [
                [
                    'i'        => 345,
                    'b'        => false,
                    'f'        => 3.5,
                    's'        => '345',
                    'children' => [
                        [
                            'i' => 345,
                            'b' => false,
                            'f' => 3.5,
                            's' => '345',
                        ],
                    ],
                ],
                [
                    'i'        => 567,
                    'b'        => true,
                    'f'        => 5.7,
                    's'        => '567',
                    'children' => [
                        // empty
                    ],
                ],
            ],
        ]);

        $this->assertEquals($expected, $actual);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JsonFactoryTest_Parent extends JsonFactory {
    public int    $i;
    public bool   $b;
    public float  $f;
    public string $s;

    /**
     * @var array<mixed>
     */
    public array $array;

    /**
     * @var array<\App\Services\DataLoader\Utils\JsonFactoryTest_Child>
     */
    public array $children;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JsonFactoryTest_Child extends JsonFactory {
    public int    $i;
    public bool   $b;
    public float  $f;
    public string $s;

    /**
     * @var array<int, \App\Services\DataLoader\Utils\JsonFactoryTest_Child>
     */
    public array $children;
}
