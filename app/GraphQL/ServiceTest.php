<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\Organization;
use App\Services\I18n\Locale;
use App\Services\Organization\OrganizationProvider;
use Exception;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Mockery\MockInterface;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use ReflectionClass;
use stdClass;
use Tests\TestCase;

use function is_string;
use function sprintf;
use function strtr;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getKey
     * @covers ::getDefaultKey
     *
     * @dataProvider dataProviderGetKey
     *
     * @param array<object|string>|object|string $key
     */
    public function testGetKey(Exception|string $expected, string $locale, object|array|string $key): void {
        $this->override(Locale::class, static function (MockInterface $mock) use ($locale): void {
            $mock
                ->shouldReceive('get')
                ->atLeast()
                ->once()
                ->andReturn($locale);
        });

        $locale  = $this->app->get(Locale::class);
        $service = new class($locale) extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Locale $locale,
            ) {
                // empty
            }

            public function getKey(object|array|string $key): string {
                return parent::getKey($key);
            }
        };

        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        if (is_string($expected)) {
            $expected = strtr($expected, [
                '${service}' => $service::class,
            ]);
        }

        $this->assertEquals($expected, $service->getKey($key));
    }

    /**
     * @covers ::getKeyPart
     *
     * @dataProvider dataProviderGetKeyPart
     */
    public function testGetKeyPart(Exception|string $expected, object|string $value): void {
        $service = new class() extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty;
            }

            public function getKeyPart(object|string $value): string {
                return parent::getKeyPart($value);
            }
        };

        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $this->assertEquals($expected, $service->getKeyPart($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{\Exception|string,string,array<object|string>|object|string}>
     */
    public function dataProviderGetKey(): array {
        return [
            'string'             => ['${service}:en-GB:abc', 'en-GB', 'abc'],
            'object'             => ['${service}:en-GB:'.stdClass::class, 'en-GB', new stdClass()],
            'model'              => [
                '${service}:en-GB:ServiceTest_Model:123',
                'en-GB',
                new ServiceTest_Model('123'),
            ],
            'model (not exists)' => [
                new InvalidArgumentException(sprintf(
                    'The instance of `%s` should exist and have a non-empty key.',
                    ServiceTest_Model::class,
                )),
                'en-GB',
                new ServiceTest_Model('123', false),
            ],
            'model (no key)'     => [
                new InvalidArgumentException(sprintf(
                    'The instance of `%s` should exist and have a non-empty key.',
                    ServiceTest_Model::class,
                )),
                'en-GB',
                new ServiceTest_Model(),
            ],
            'array'              => [
                '${service}:en-GB:Organization:00000000-0000-0000-0000-000000000000:@directive:ServiceTest_Model:345',
                'en-GB',
                [
                    new ServiceTest_Organization('53d05aa0-9062-4068-bb61-2705b14dc239', true),
                    new ServiceTest_Directive(),
                    new ServiceTest_Model('345'),
                ],
            ],
        ];
    }

    /**
     * @return array<string,array{\Exception|string,object|string}>
     */
    public function dataProviderGetKeyPart(): array {
        return [
            'directive'         => [
                '@directive',
                new ServiceTest_Directive(),
            ],
            'organization'      => [
                'Organization:db5b78c0-65a3-4cba-b045-a985f79b3abb',
                new ServiceTest_Organization('db5b78c0-65a3-4cba-b045-a985f79b3abb', false),
            ],
            'root organization' => [
                'Organization:00000000-0000-0000-0000-000000000000',
                new ServiceTest_Organization('91f3204d-427b-40ad-ae19-187ce41d987c', true),
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
class ServiceTest_Model extends Model {
    public function __construct(string $key = null, bool $exists = true) {
        parent::__construct([]);

        $this->{$this->getKeyName()} = $key;
        $this->exists                = $exists;
    }

    public function getMorphClass(): string {
        return (new ReflectionClass($this))->getShortName();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServiceTest_Directive extends BaseDirective {
    public static function definition(): string {
        return '';
    }

    public function name(): string {
        return 'directive';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServiceTest_Organization extends OrganizationProvider {
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
        return null;
    }
}
