<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Models\Data\Status as StatusModel;
use App\Models\Data\Type as TypeModel;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Service;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Setting;
use App\Services\Settings\Settings;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\Type;
use Closure;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use stdClass;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Application\Settings
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $translationsFactory = null,
        object $store = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setTranslations($translationsFactory);

        if ($store) {
            $service = new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Environment::class),
                $store::class,
            ) extends Settings {
                /**
                 * @noinspection PhpMissingParentConstructorInspection
                 *
                 * @param class-string $store
                 */
                public function __construct(
                    protected Application $app,
                    protected Repository $config,
                    protected Environment $environment,
                    protected string $store,
                ) {
                    // empty
                }

                public function getStore(): string {
                    return $this->store;
                }

                public function isReadonly(Setting $setting): bool {
                    return $setting->getName() === 'SETTING_READONLY'
                        || parent::isReadonly($setting);
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
                            values {
                                ... on Type {
                                  __typename
                                  id
                                  name
                                }
                                ... on Status {
                                  __typename
                                  id
                                  name
                                }
                                ... on StringValue {
                                  __typename
                                  value
                                }
                            }
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
            new AuthOrgRootDataProvider('application'),
            new AuthRootDataProvider('application'),
            new ArrayDataProvider([
                Constants::class        => [
                    new GraphQLSuccess('application'),
                ],
                'internal not returned' => [
                    new GraphQLSuccess('application', [
                        'settings' => [
                            [
                                'name'        => 'SETTING_FLOAT',
                                'type'        => 'Float',
                                'array'       => false,
                                'value'       => 'null',
                                'values'      => null,
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
                                'values'      => null,
                                'secret'      => false,
                                'default'     => 'false',
                                'readonly'    => false,
                                'job'         => false,
                                'service'     => false,
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_ARRAY',
                                'type'        => 'SettingsTest_TypeWithValues',
                                'array'       => true,
                                'value'       => 'null',
                                'values'      => [
                                    [
                                        '__typename' => 'Type',
                                        'id'         => '3dd66188-0fb9-408e-8d7d-80700ba182de',
                                        'name'       => 'type-a',
                                    ],
                                    [
                                        '__typename' => 'Status',
                                        'id'         => 'fbeb61a2-5f30-4abc-bea8-47e777eec4ee',
                                        'name'       => 'status-a',
                                    ],
                                    [
                                        '__typename' => 'StringValue',
                                        'value'      => 'string',
                                    ],
                                ],
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
                                'values'      => null,
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
                                'values'      => null,
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
                                'values'      => null,
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
                                'values'      => null,
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
                                'values'      => null,
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
                        #[InternalAttribute]
                        #[SettingAttribute('test.internal')]
                        public const SETTING_INTERNAL = 'internal';

                        /**
                         * Summary summary summary summary summary summary summary.
                         */
                        #[SettingAttribute('test.float')]
                        public const SETTING_FLOAT = 123.4;

                        #[SettingAttribute('test.bool')]
                        public const SETTING_BOOL = false;

                        #[SettingAttribute('test.array')]
                        #[TypeAttribute(SettingsTest_TypeWithValues::class)]
                        public const SETTING_ARRAY = [123, 345];

                        #[SecretAttribute]
                        #[SettingAttribute('test.array')]
                        #[TypeAttribute(IntType::class)]
                        public const SETTING_ARRAY_SECRET = [123, 345];

                        #[SecretAttribute]
                        #[SettingAttribute('test.secret')]
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

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SettingsTest_TypeWithValues extends Type {
    public function getValues(): Collection|array|null {
        return [
            TypeModel::factory()->make([
                'id'   => '3dd66188-0fb9-408e-8d7d-80700ba182de',
                'name' => 'type-a',
            ]),
            StatusModel::factory()->make([
                'id'   => 'fbeb61a2-5f30-4abc-bea8-47e777eec4ee',
                'name' => 'status-a',
            ]),
            'string',
        ];
    }
}
