<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Service;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Settings;
use App\Services\Settings\Types\IntType;
use Closure;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use stdClass;
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
        Closure $translationsFactory = null,
        object $store = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));
        $this->setTranslations($translationsFactory);

        if ($store) {
            $service = new class(
                $this->app,
                $this->app->make(Repository::class),
                $store::class,
            ) extends Settings {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct(
                    protected Application $app,
                    protected Repository $config,
                    protected string $store,
                ) {
                    // empty
                }

                public function getStore(): string {
                    return $this->store;
                }

                protected function isOverridden(string $name): bool {
                    return $name === 'SETTING_READONLY' || parent::isOverridden($name);
                }
            };

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
                            readonly
                            job
                            service
                            description
                        }
                    }
                }
            ')
            ->assertThat($expected);
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
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => false,
                                'description' => 'Summary summary summary summary summary summary summary.',
                            ],
                            [
                                'name'        => 'SETTING_BOOL',
                                'type'        => 'Boolean',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => 'false',
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => false,
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_ARRAY',
                                'type'        => 'Int',
                                'array'       => true,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => '123,345',
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => false,
                                'description' => 'Array array array array array.',
                            ],
                            [
                                'name'        => 'SETTING_ARRAY_SECRET',
                                'type'        => 'Int',
                                'array'       => true,
                                'value'       => 'null',
                                'secret'      => true,
                                'default'     => '********,********',
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => false,
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_SECRET',
                                'type'        => 'String',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => true,
                                'default'     => '********',
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => false,
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_READONLY',
                                'type'        => 'String',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => 'readonly',
                                'readonly'    => true,
                                'job'         => false,
                                'service'     => false,
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_JOB',
                                'type'        => 'String',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => 'test',
                                'readonly'    => false,
                                'job'         => true,
                                'service'     => false,
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_SERVICE',
                                'type'        => 'Boolean',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => 'true',
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => true,
                                'description' => null,
                            ],
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        return [
                            $locale => [
                                'settings.descriptions.SETTING_ARRAY' => 'Array array array array array.',
                            ],
                        ];
                    },
                    new class() {
                        #[SettingAttribute('test.internal')]
                        #[InternalAttribute]
                        public const SETTING_INTERNAL = 'internal';

                        /**
                         * Summary summary summary summary summary summary summary.
                         */
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

                        #[SettingAttribute('test.secret')]
                        #[SecretAttribute]
                        public const SETTING_SECRET = 'secret';

                        #[SettingAttribute('test.readonly')]
                        public const SETTING_READONLY = 'readonly';

                        #[Job(stdClass::class, 'queue')]
                        public const SETTING_JOB = 'test';

                        #[Service(stdClass::class, 'enabled')]
                        public const SETTING_SERVICE = true;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
