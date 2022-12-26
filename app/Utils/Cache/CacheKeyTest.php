<?php declare(strict_types = 1);

namespace App\Utils\Cache;

use App\Models\Organization;
use App\Services\I18n\CurrentLocale;
use App\Services\I18n\CurrentTimezone;
use App\Services\Organization\OrganizationProvider;
use App\Services\Queue\Contracts\NamedJob;
use ArrayIterator;
use DateTime;
use DateTimeInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use League\Geotools\Geohash\Geohash;
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

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Cache\CacheKey
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
            self::expectExceptionObject($expected);
        }

        self::assertEquals($expected, (string) new CacheKey($key));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{Exception|string,array<mixed>}>
     */
    public function dataProviderToString(): array {
        return [
            'bool'                                        => [
                new CacheKeyInvalidValue(true),
                [
                    true,
                ],
            ],
            'float'                                       => [
                new CacheKeyInvalidValue(1.23),
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
                    123,
                    456,
                ],
            ],
            'null'                                        => [
                '',
                [
                    null,
                ],
            ],
            'object'                                      => [
                new CacheKeyInvalidValue(new stdClass()),
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
                new CacheKeyInvalidModel(new CacheKeyTest_Model()),
                [
                    new CacheKeyTest_Model('7e9604a8-ad67-4062-b3aa-3d347562a2ed', false),
                ],
            ],
            Model::class.' (no key)'                      => [
                new CacheKeyInvalidModel(new CacheKeyTest_Model()),
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
            CurrentLocale::class                          => [
                'en_GB',
                [
                    new CacheKeyTest_CurrentLocale('en_GB'),
                ],
            ],
            CurrentTimezone::class                        => [
                'Europe/Paris',
                [
                    new CacheKeyTest_CurrentTimezone('Europe/Paris'),
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
            OrganizationProvider::class.' (undefined)'    => [
                '',
                [
                    new CacheKeyTest_OrganizationProvider(null, false),
                ],
            ],
            BaseDirective::class                          => [
                '@directive',
                [
                    new CacheKeyTest_Directive('directive'),
                ],
            ],
            DateTimeInterface::class                      => [
                '2022-06-02T145835',
                [
                    new DateTime('2022-06-02T16:58:35+02:00'),
                ],
            ],
            Traversable::class                            => [
                sha1(json_encode(['a' => 123, 'b' => 'value'], JSON_THROW_ON_ERROR)),
                [
                    new ArrayIterator(['b' => 'value', 'a' => 123]),
                ],
            ],
            JsonSerializable::class                       => [
                sha1(json_encode('json', JSON_THROW_ON_ERROR)),
                [
                    new CacheKeyTest_JsonSerializable('json'),
                ],
            ],
            Geohash::class.' (encode)'                    => [
                sha1(json_encode(['spey'], JSON_THROW_ON_ERROR)),
                [
                    [
                        (new Geohash())->encode((new Geohash())->decode('spey61y')->getCoordinate(), 4),
                    ],
                ],
            ],
            Geohash::class.' (decode)'                    => [
                sha1(json_encode(['spey61ys0000'], JSON_THROW_ON_ERROR)),
                [
                    [
                        (new Geohash())->decode('spey61y'),
                    ],
                ],
            ],
            Command::class                                => [
                sha1(json_encode(['$tests-test-command'], JSON_THROW_ON_ERROR)),
                [
                    [
                        new CacheKeyTest_Command(),
                    ],
                ],
            ],
            Command::class.' (no name)'                   => [
                new CacheKeyInvalidCommand(new CacheKeyTest_CommandWithoutName()),
                [
                    [
                        new CacheKeyTest_CommandWithoutName(),
                    ],
                ],
            ],
            CacheKey::class                               => [
                'a:b:c',
                [
                    new CacheKey(['a', 'b', 'c']),
                ],
            ],
            'array (nested)'                              => [
                sha1(json_encode(['a' => 123, 'b' => 'value', 'c' => true], JSON_THROW_ON_ERROR)),
                [
                    ['b' => 'value', 'a' => 123, 'c' => true],
                ],
            ],
            'array (assoc)'                               => [
                'value:test',
                [
                    'b' => 'value',
                    'a' => 'test',
                ],
            ],
            'list'                                        => [
                '4:2:1',
                [
                    '4',
                    '2',
                    '1',
                ],
            ],
            'list (nested, unordered keys)'               => [
                sha1(json_encode([1, 1, 3], JSON_THROW_ON_ERROR)),
                [
                    [1, 3, 4 => 1],
                ],
            ],
            'list (nested)'                               => [
                sha1(json_encode([1, 2, 4], JSON_THROW_ON_ERROR)),
                [
                    [4, 1, 2],
                ],
            ],
            'complex'                                     => [
                implode(':', [
                    CacheKeyTest_CacheKeyable::class,
                    'en_GB',
                    'Organization',
                    '00000000-0000-0000-0000-000000000000',
                    sha1(json_encode(['a' => 456, 'b' => '123'], JSON_THROW_ON_ERROR)),
                ]),
                [
                    new CacheKeyTest_CacheKeyable(),
                    new CacheKeyTest_CurrentLocale('en_GB'),
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
 *
 * @property string $id
 */
class CacheKeyTest_Model extends Model {
    public function __construct(string|int $key = null, bool $exists = true) {
        parent::__construct([]);

        $this->id      = $key;
        $this->exists  = $exists;
        $this->keyType = is_string($key) ? 'string' : 'int';
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
        protected ?string $key,
        protected bool $root,
    ) {
        // empty
    }

    public function getKey(): string {
        return (string) $this->key;
    }

    public function isRoot(): bool {
        return $this->root;
    }

    protected function getCurrent(): ?Organization {
        return $this->getKey()
            ? Organization::factory()->make(['id' => $this->getKey()])
            : null;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_CurrentLocale extends CurrentLocale {
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

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_CurrentTimezone extends CurrentTimezone {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected string $timezone,
    ) {
        // empty
    }

    public function get(): string {
        return $this->timezone;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class CacheKeyTest_JsonSerializable implements JsonSerializable {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected mixed $data,
    ) {
        // empty
    }

    public function jsonSerialize(): mixed {
        return $this->data;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @property string $id
 */
class CacheKeyTest_Command extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'tests:test-command';
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @property string $id
 */
class CacheKeyTest_CommandWithoutName extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'tests:test-command-without-name';

    public function getName(): ?string {
        return null;
    }
}
