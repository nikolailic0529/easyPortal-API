<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Setting;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use App\Services\Settings\Types\StringType;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\UpdateApplicationSettings
 */
class UpdateApplicationSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<array{name: string, value: string}> $input
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $translationsFactory = null,
        object $store = null,
        array $input = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        // Mock
        if ($store) {
            $service = new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Dispatcher::class),
                $this->app->make(Storage::class),
                $this->app->make(Environment::class),
                $store::class,
            ) extends Settings {
                public function __construct(
                    Application $app,
                    Repository $config,
                    Dispatcher $dispatcher,
                    Storage $storage,
                    Environment $environment,
                    protected string $store,
                ) {
                    parent::__construct($app, $config, $dispatcher, $storage, $environment);
                }

                protected function getStore(): string {
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
                mutation updateApplicationSettings($input: [UpdateApplicationSettingsInput!]!) {
                    updateApplicationSettings(input:$input){
                        updated {
                            name
                            type
                            array
                            value
                            secret
                            default
                            description
                        }
                    }
                }', ['input' => $input])
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
            new RootOrganizationDataProvider('updateApplicationSettings'),
            new RootUserDataProvider('updateApplicationSettings'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('updateApplicationSettings', UpdateApplicationSettings::class, [
                        'updated' => [
                            [
                                'name'        => 'SETTING_INT',
                                'type'        => 'Int',
                                'array'       => false,
                                'value'       => '345',
                                'secret'      => false,
                                'default'     => '123',
                                'description' => 'Description description description description.',
                            ],
                            [
                                'name'        => 'SETTING_SECRET',
                                'type'        => 'String',
                                'array'       => false,
                                'value'       => '********',
                                'secret'      => true,
                                'default'     => '********',
                                'description' => null,
                            ],
                            [
                                'name'        => 'SETTING_STRING',
                                'type'        => 'String',
                                'array'       => false,
                                'value'       => 'null',
                                'secret'      => false,
                                'default'     => 'null',
                                'description' => 'String string string string string.',
                            ],
                            [
                                'name'        => 'SETTING_ARRAY',
                                'type'        => 'String',
                                'array'       => true,
                                'value'       => 'last,win',
                                'secret'      => false,
                                'default'     => 'abc,de',
                                'description' => null,
                            ],
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        return [
                            $locale => [
                                'settings.descriptions.SETTING_STRING' => 'String string string string string.',
                            ],
                        ];
                    },
                    new class() {
                        /**
                         * Description description description description.
                         */
                        #[SettingAttribute('int')]
                        public const SETTING_INT = 123;

                        #[SettingAttribute('secret')]
                        #[SecretAttribute]
                        public const SETTING_SECRET = 'secret';

                        #[SettingAttribute('string')]
                        #[TypeAttribute(StringType::class)]
                        public const SETTING_STRING = null;

                        #[SettingAttribute('array')]
                        #[TypeAttribute(StringType::class)]
                        public const SETTING_ARRAY = ['abc', 'de'];

                        #[SettingAttribute('internal')]
                        #[InternalAttribute]
                        public const SETTING_INTERNAL = 'internal';

                        #[SettingAttribute('test.readonly')]
                        public const SETTING_READONLY = 'readonly';
                    },
                    [
                        [
                            'name'  => 'SETTING_INT',
                            'value' => '345',
                        ],
                        [
                            'name'  => 'SETTING_SECRET',
                            'value' => '********',
                        ],
                        [
                            'name'  => 'SETTING_STRING',
                            'value' => 'null',
                        ],
                        [
                            'name'  => 'SETTING_ARRAY',
                            'value' => 'abc,de',
                        ],
                        [
                            'name'  => 'SETTING_ARRAY',
                            'value' => 'last,win',
                        ],
                        [
                            'name'  => 'SETTING_UNKNOWN',
                            'value' => 'unknown (must be ignored)',
                        ],
                        [
                            'name'  => 'SETTING_INTERNAL',
                            'value' => 'must not be changed',
                        ],
                        [
                            'name'  => 'SETTING_READONLY',
                            'value' => 'must not be changed',
                        ],
                    ],
                    true,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
