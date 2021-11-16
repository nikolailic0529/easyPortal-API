<?php declare(strict_types = 1);

namespace App\Utils;

use App\Models\Organization;
use App\Services\I18n\Locale;
use App\Services\Organization\OrganizationProvider;
use App\Services\Queue\NamedJob;
use ArrayIterator;
use Exception;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use ReflectionClass;
use stdClass;
use Tests\TestCase;
use Traversable;

use function implode;
use function is_string;
use function json_encode;
use function sha1;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Utils\CacheKey
 */
class CacheKeyTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__toString
     *
     * @dataProvider dataProviderToString
     *
     * @param array<mixed> $key
     */
    public function testToString(Exception|string $expected, array $key): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $this->assertEquals($expected, (string) new CacheKey($key));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{\Exception|string,array<object|string>|object|string}>
     */
    public function dataProviderToString(): array {
        return [
            'bool'                                        => [
                new InvalidArgumentException('The `$value` cannot be used as a key.'),
                [
                    true,
                ],
            ],
            'float'                                       => [
                new InvalidArgumentException('The `$value` cannot be used as a key.'),
                [
                    1.23,
                ],
            ],
            'string'                                      => [
                'string:abc',
                [
                    'string',
                    'abc',
                ],
            ],
            'int'                                         => [
                '123:456',
                [
                    '123',
                    '456',
                ],
            ],
            'null'                                        => [
                '',
                [
                    null,
                ],
            ],
            'object'                                      => [
                new InvalidArgumentException('The `$value` cannot be used as a key.'),
                [
                    new stdClass(),
                ],
            ],
            Model::class.' (int key)'                     => [
                'CacheKeyTest_Model:123',
                [
                    new CacheKeyTest_Model(123),
                ],
            ],
            Model::class.' (string key)'                  => [
                'CacheKeyTest_Model:04deda4a-8d77-406f-8f82-d69bcd756cfa',
                [
                    new CacheKeyTest_Model('04deda4a-8d77-406f-8f82-d69bcd756cfa'),
                ],
            ],
            Model::class.' (not exists)'                  => [
                new InvalidArgumentException(sprintf(
                    'The instance of `%s` model should exist and have a non-empty key.',
                    CacheKeyTest_Model::class,
                )),
                [
                    new CacheKeyTest_Model('7e9604a8-ad67-4062-b3aa-3d347562a2ed', false),
                ],
            ],
            Model::class.' (no key)'                      => [
                new InvalidArgumentException(sprintf(
                    'The instance of `%s` model should exist and have a non-empty key.',
                    CacheKeyTest_Model::class,
                )),
                [
                    new CacheKeyTest_Model(),
                ],
            ],
            QueueableEntity::class.'(without connection)' => [
                sprintf(
                    '%s::1',
                    CacheKeyTest_QueueableEntity::class,
                ),
                [
                    'a' => new CacheKeyTest_QueueableEntity(1),
                ],
            ],
            QueueableEntity::class.'(with connection)'    => [
                sprintf(
                    '%s:connection:2',
                    CacheKeyTest_QueueableEntity::class,
                ),
                [
                    'a' => new CacheKeyTest_QueueableEntity(2, 'connection'),
                ],
            ],
            NamedJob::class                               => [
                'job',
                [
                    new CacheKeyTest_NamedJob('job'),
                ],
            ],
            CacheKeyable::class                           => [
                CacheKeyTest_CacheKeyable::class,
                [
                    new CacheKeyTest_CacheKeyable(),
                ],
            ],
            Locale::class                                 => [
                'en_GB',
                [
                    new CacheKeyTest_Locale('en_GB'),
                ],
            ],
            OrganizationProvider::class.' (non root)'     => [
                'Organization:f1067c2c-ce60-46de-9be7-ac0b82d70f70',
                [
                    new CacheKeyTest_OrganizationProvider('f1067c2c-ce60-46de-9be7-ac0b82d70f70', false),
                ],
            ],
            OrganizationProvider::class.' (root)'         => [
                'Organization:00000000-0000-0000-0000-000000000000',
                [
                    new CacheKeyTest_OrganizationProvider('204fb12f-3c13-4fab-9a31-895e0f1e2647', true),
                ],
            ],
            BaseDirective::class                          => [
                '@directive',
                [
                    new CacheKeyTest_Directive('directive'),
                ],
            ],
            Traversable::class                            => [
                sha1(json_encode(['a' => 123, 'b' => 'value'])),
                [
                    new ArrayIterator(['b' => 'value', 'a' => 123]),
                ],
            ],
            'array'                                       => [
                sha1(json_encode(['a' => 123, 'b' => 'value'])),
                [
                    ['b' => 'value', 'a' => 123],
                ],
            ],
            'complex'                                     => [
                implode(':', [
                    CacheKeyTest_CacheKeyable::class,
                    'en_GB',
                    'Organization',
                    '00000000-0000-0000-0000-000000000000',
                    sha1(json_encode(['a' => 456, 'b' => '123'])),
                ]),
                [
                    new CacheKeyTest_CacheKeyable(),
                    new CacheKeyTest_Locale('en_GB'),
                    new CacheKeyTest_OrganizationProvider('a9206d65-74c7-4367-bf8a-44c99bc7396d', true),
                    ['b' => '123', 'a' => 456],
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
class CacheKeyTest_Model extends Model {
    public function __construct(string|int $key = null, bool $exists = true) {
        parent::__construct([]);

        $this->{$this->getKeyName()} = $key;
        $this->exists                = $exists;
        $this->keyType               = is_string($key) ? 'string' : 'int';
    }

    public function getMorphClass(): string {
        return (new ReflectionClass($this))->getShortName();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_QueueableEntity implements QueueableEntity {
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
    public function getQueueableRelations(): array {
        return $this->relations;
    }

    public function getQueueableConnection(): ?string {
        return $this->connection;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_NamedJob implements NamedJob {
    public function __construct(
        protected string $name,
    ) {
        // empty
    }

    public function displayName(): string {
        return $this->name;
    }
}


/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_CacheKeyable implements CacheKeyable {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_Directive extends BaseDirective {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected string $name,
    ) {
        // empty
    }

    public static function definition(): string {
        return '';
    }

    public function name(): string {
        return $this->name;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_OrganizationProvider extends OrganizationProvider {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected string $key,
        protected bool $root,
    ) {
        // empty
    }

    public function getKey(): string {
        return $this->key;
    }

    public function isRoot(): bool {
        return $this->root;
    }

    protected function getCurrent(): ?Organization {
        return Organization::factory()->make(['id' => $this->getKey()]);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_Locale extends Locale {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected string $locale,
    ) {
        // empty
    }

    public function get(): string {
        return $this->locale;
    }
}
