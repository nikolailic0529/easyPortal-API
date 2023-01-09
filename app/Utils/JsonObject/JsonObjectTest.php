<?php declare(strict_types = 1);

namespace App\Utils\JsonObject;

use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Tests\TestCase;

use function count;
use function filter_var;
use function json_encode;
use function sha1;
use function sprintf;
use function tap;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @coversDefaultClass \App\Utils\JsonObject\JsonObject
 */
class JsonObjectTest extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testConstruct(): void {
        $expected = tap(new JsonObjectTest_Parent(), static function (JsonObjectTest_Parent $parent): void {
            $parent->i          = 123;
            $parent->b          = true;
            $parent->f          = 1.2;
            $parent->s          = '123';
            $parent->d          = Date::make('2018-05-23T13:43:32+00:00');
            $parent->nullable   = null;
            $parent->normalized = true;
            $parent->array      = [
                'i' => 123,
                'b' => true,
                'f' => 1.2,
                's' => '123',
            ];
            $parent->children   = [
                tap(new JsonObjectTest_Child(), static function (JsonObjectTest_Child $child): void {
                    $child->i          = 345;
                    $child->b          = false;
                    $child->f          = 3.5;
                    $child->s          = '345';
                    $child->d          = Date::make('2021-05-23T13:43:32+00:00');
                    $child->normalized = null;
                    $child->children   = [
                        tap(new JsonObjectTest_Child(), static function (JsonObjectTest_Child $child): void {
                            $child->i = 345;
                            $child->b = false;
                            $child->f = 3.5;
                            $child->s = '345';
                            $child->d = null;
                        }),
                    ];
                }),
                tap(new JsonObjectTest_Child(), static function (JsonObjectTest_Child $child): void {
                    $child->i        = 567;
                    $child->b        = true;
                    $child->f        = 5.7;
                    $child->s        = '567';
                    $child->children = null;
                }),
            ];
        });
        $actual   = new JsonObjectTest_Parent([
            'i'          => 123,
            'b'          => true,
            'f'          => 1.2,
            's'          => '123',
            'd'          => '2018-05-23T13:43:32+00:00',
            'nullable'   => null,
            'normalized' => '1',
            'array'      => [
                'i' => 123,
                'b' => true,
                'f' => 1.2,
                's' => '123',
            ],
            'children'   => [
                [
                    'i'          => 345,
                    'b'          => false,
                    'f'          => 3.5,
                    's'          => '345',
                    'd'          => '2021-05-23T13:43:32+00:00',
                    'normalized' => 'sfsdf',
                    'children'   => [
                        [
                            'i' => 345,
                            'b' => false,
                            'f' => 3.5,
                            's' => '345',
                            'd' => null,
                        ],
                    ],
                ],
                [
                    'i'        => 567,
                    'b'        => true,
                    'f'        => 5.7,
                    's'        => '567',
                    'children' => null,
                ],
            ],
        ]);

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::getProperties
     */
    public function testGetProperties(): void {
        $child  = new JsonObjectTest_Child([
            'i' => 123,
        ]);
        $object = new JsonObjectTest_Child([
            'i'        => 567,
            'b'        => true,
            'children' => [$child],
        ]);

        self::assertEquals(
            [
                'i'        => 567,
                'b'        => true,
                'children' => [$child],
            ],
            $object->getProperties(),
        );
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize(): void {
        $child  = new JsonObjectTest_Child([
            'i' => 123,
        ]);
        $object = new JsonObjectTest_Child([
            'i'        => 567,
            'b'        => true,
            'children' => [$child],
        ]);

        self::assertEquals(
            [
                'i'        => 567,
                'b'        => true,
                'children' => [$child],
            ],
            $object->jsonSerialize(),
        );
    }

    /**
     * @covers ::toArray
     */
    public function testToArray(): void {
        $child  = new JsonObjectTest_Child([
            'i' => 123,
        ]);
        $object = new JsonObjectTest_Child([
            'i'        => 567,
            'b'        => true,
            'children' => [$child],
        ]);

        self::assertEquals(
            [
                'i'        => 567,
                'b'        => true,
                'children' => [
                    [
                        'i' => 123,
                    ],
                ],
            ],
            $object->toArray(),
        );
    }

    /**
     * @covers ::__get
     */
    public function testGetDynamicProperties(): void {
        $object = new class() extends JsonObject {
            // empty
        };

        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'Property `%s::$%s` doesn\'t exist.',
            $object::class,
            'unknown',
        )));

        /** @phpstan-ignore-next-line needed for test */
        self::assertNotNull($object->unknown);
    }

    /**
     * @cover ::__set
     */
    public function testSetDynamicProperties(): void {
        $object = new class() extends JsonObject {
            // empty
        };

        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'Property `%s::$%s` doesn\'t exist.',
            $object::class,
            'unknown',
        )));

        $object->unknown = 'value'; // @phpstan-ignore-line needed for test
    }

    /**
     * @cover ::__isset
     */
    public function testIssetDynamicProperties(): void {
        $object = new class() extends JsonObject {
            public string $known = 'value';
        };

        self::assertTrue(isset($object->known));
        self::assertFalse(isset($object->unknown)); // @phpstan-ignore-line needed for test
    }

    /**
     * @covers ::isEmpty
     */
    public function testIsEmpty(): void {
        $empty  = new class() extends JsonObject {
            public string $property;
        };
        $object = new class(['property' => 'value']) extends JsonObject {
            public string $property;
        };

        self::assertTrue($empty->isEmpty());
        self::assertFalse($object->isEmpty());
    }

    /**
     * @covers ::count
     */
    public function testCount(): void {
        $empty  = new class() extends JsonObject {
            public string $property;
        };
        $object = new class(['property' => 'value']) extends JsonObject {
            public string $property;
        };

        self::assertEquals(0, count($empty));
        self::assertEquals(1, count($object));
    }

    /**
     * @covers ::make
     */
    public function testMake(): void {
        self::assertNull(
            JsonObjectTest_Parent::make(null),
        );
        self::assertEquals(
            [
                new JsonObjectTest_Parent(['i' => 1]),
                new JsonObjectTest_Parent(['i' => 2]),
            ],
            JsonObjectTest_Parent::make([
                ['i' => 1],
                ['i' => 2],
            ]),
        );
    }

    /**
     * @covers ::getHash
     */
    public function testGetHash(): void {
        $child  = new JsonObjectTest_Child([
            'i' => 123,
        ]);
        $object = new JsonObjectTest_Child([
            'i'        => 567,
            'b'        => true,
            'children' => [$child],
        ]);

        self::assertEquals(
            sha1(json_encode(
                [
                    'i'        => 567,
                    'b'        => true,
                    'children' => [
                        [
                            'i' => 123,
                        ],
                    ],
                ],
                JSON_THROW_ON_ERROR,
            )),
            $object->getHash(),
        );
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JsonObjectTest_Parent extends JsonObject {
    public int               $i;
    public bool              $b;
    public float             $f;
    public string            $s;
    public DateTimeInterface $d;

    /**
     * @var array<mixed>
     */
    public array $array;

    /**
     * @var array<JsonObjectTest_Child>
     */
    #[JsonObjectArray(JsonObjectTest_Child::class)]
    public array $children;

    /**
     * @var array<mixed>|null
     */
    public array|null $nullable;

    #[JsonObjectNormalizer(JsonObjectTest_Normalizer::class)]
    public bool $normalized;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JsonObjectTest_Child extends JsonObject {
    public int                $i;
    public bool               $b;
    public float              $f;
    public string             $s;
    public ?DateTimeInterface $d;

    /**
     * @var array<int, JsonObjectTest_Child>
     */
    #[JsonObjectArray(JsonObjectTest_Child::class)]
    public array|null $children;

    #[JsonObjectNormalizer(JsonObjectTest_Normalizer::class)]
    public ?bool $normalized;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JsonObjectTest_Normalizer implements Normalizer {
    public static function normalize(mixed $value): mixed {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
