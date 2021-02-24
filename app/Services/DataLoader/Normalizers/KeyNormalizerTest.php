<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Illuminate\Contracts\Queue\QueueableEntity;
use PHPUnit\Framework\TestCase;

use function addslashes;
use function sprintf;
use function strtolower;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizers\KeyNormalizer
 */
class KeyNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(string $expected, mixed $value): void {
        $this->assertEquals($expected, (new KeyNormalizer())->normalize($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalize(): array {
        return [
            'string'                             => ['"string"', 'string'],
            'null'                               => ['null', null],
            'array'                              => [
                '[1,2,3,[4,3,2.2],true,null]',
                [1, 2, 3, [4, 3, 2.2], true, null],
            ],
            'assoc'                              => [
                '{"a":"a","b":123,"c":true,"nested":{"a":"a","b":123,"c":true}}',
                [
                    'b'      => 123,
                    'c'      => true,
                    'a'      => 'a',
                    'nested' => [
                        'c' => true,
                        'b' => 123,
                        'a' => 'a',
                    ],
                ],
            ],
            'QueueableEntity without connection' => [
                sprintf(
                    '{"a":["%s",null,456],"b":123,"c":true}',
                    addslashes(strtolower(KeyNormalizerTest_QueueableEntity::class)),
                ),
                [
                    'b' => 123,
                    'c' => true,
                    'a' => new KeyNormalizerTest_QueueableEntity(456),
                ],
            ],
            'QueueableEntity with connection'    => [
                sprintf(
                    '{"a":["%s","connection",789],"b":123,"c":true}',
                    addslashes(strtolower(KeyNormalizerTest_QueueableEntity::class)),
                ),
                [
                    'b' => 123,
                    'c' => true,
                    'a' => new KeyNormalizerTest_QueueableEntity(789, 'connection'),
                ],
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
 */
class KeyNormalizerTest_QueueableEntity implements QueueableEntity {
    private mixed $id;
    /**
     * @var array<string>
     */
    private array   $relations;
    private ?string $connection;

    public function __construct(mixed $id, string $connection = null) {
        $this->id         = $id;
        $this->relations  = ['ignored'];
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function getQueueableId() {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getQueueableRelations() {
        return $this->relations;
    }

    /**
     * @inheritdoc
     */
    public function getQueueableConnection() {
        return $this->connection;
    }
}

// @phpcs:enable
