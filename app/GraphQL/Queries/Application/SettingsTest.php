<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\GraphQL\Queries\Application\Settings as SettingsQuery;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Setting;
use App\Services\Settings\Settings;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use Closure;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use ReflectionClassConstant;
use Tests\DataProviders\GraphQL\RootDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Settings
 */
class SettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        object $store = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($store) {
            $service = Mockery::mock(Settings::class, [
                $this->app->make(Repository::class),
            ]);
            $service->makePartial();
            $service->shouldAllowMockingProtectedMethods();

            $service
                ->shouldReceive('getStore')
                ->once()
                ->andReturn($store::class);

            $this->app->bind(Settings::class, static function () use ($service): Settings {
                return $service;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    application {
                        settings {
                            name
                            type
                            array
                            value
                            secret
                            default
                            description
                        }
                    }
                }
            ')
            ->assertThat($expected);
    }

    /**
     * @covers ::toArray
     *
     * @dataProvider dataProviderToArray
     *
     * @param array<mixed> $expected
     */
    public function testToArray(array $expected, object $store): void {
        $setting  = new Setting(
            $this->app->make(Repository::class),
            new ReflectionClassConstant($store, 'A'),
        );
        $settings = new class() extends SettingsQuery {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritdoc
             */
            public function toArray(Setting $setting): array {
                return parent::toArray($setting);
            }
        };

        $this->assertEquals($expected, $settings->toArray($setting));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new RootDataProvider('application'),
            new ArrayDataProvider([
                Constants::class        => [
                    new GraphQLSuccess('application', self::class),
                ],
                'internal not returned' => [
                    new GraphQLSuccess('application', self::class, [
                        'settings' => [
                            [
                                'name'        => 'SETTING_FLOAT',
                                'type'        => 'Float',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => '123.40',
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_BOOL',
                                'type'        => 'Boolean',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => 'false',
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_ARRAY',
                                'type'        => 'Int',
                                'array'       => true,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => '123,345',
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_ARRAY_SECRET',
                                'type'        => 'Int',
                                'array'       => true,
                                'value'       => 'null',
                                'secret'      => true,
                                'default'     => '********,********',
                                'description' => null,
                            ],
                        ],
                    ]),
                    new class() {
                        #[SettingAttribute('test.internal')]
                        #[InternalAttribute]
                        public const SETTING_INTERNAL = 'internal';

                        #[SettingAttribute('test.float')]
                        public const SETTING_FLOAT = 123.4;

                        #[SettingAttribute('test.bool')]
                        public const SETTING_BOOL = false;

                        #[SettingAttribute('test.array')]
                        #[TypeAttribute(IntType::class)]
                        public const SETTING_ARRAY = [123, 345];

                        #[SettingAttribute('test.array')]
                        #[TypeAttribute(IntType::class)]
                        #[SecretAttribute]
                        public const SETTING_ARRAY_SECRET = [123, 345];
                    },
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderToArray(): array {
        return [
            'secret' => [
                [
                    'name'        => 'A',
                    'type'        => 'String',
                    'array'       => false,
                    'value'       => 'null',
                    'secret'      => true,
                    'default'     => '********',
                    'description' => 'Summary summary summary summary summary summary summary.',
                ],
                new class() {
                    /**
                     * Summary summary summary summary summary summary summary.
                     */
                    #[SettingAttribute('a')]
                    #[SecretAttribute]
                    public const A = 'test';
                },
            ],
            'array'  => [
                [
                    'name'        => 'A',
                    'type'        => 'String',
                    'array'       => true,
                    'value'       => 'null',
                    'secret'      => false,
                    'default'     => 'test,test',
                    'description' => null,
                ],
                new class() {
                    #[SettingAttribute('a')]
                    #[TypeAttribute(StringType::class)]
                    public const A = ['test', 'test'];
                },
            ],
        ];
    }
    // </editor-fold>
}
