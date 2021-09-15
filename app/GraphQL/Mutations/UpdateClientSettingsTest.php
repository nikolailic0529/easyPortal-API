<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Filesystem\Storages\ClientSettings;
use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings as SettingsService;
use App\Services\Settings\Storage;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateClientSettings
 */
class UpdateClientSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param \LastDragon_ru\LaraASP\Testing\Constraints\Response\Response|array{
     *      response:\LastDragon_ru\LaraASP\Testing\Constraints\Response\Response,
     *      content:array<mixed>
     *      } $expected
     * @param array<mixed> $content
     * @param array{name:string,value:string} $settings
     */
    public function testInvoke(
        Response|array $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        object $store = null,
        array $content = [],
        array $settings = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        // Service
        if ($store) {
            $service = new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Storage::class),
                $this->app->make(Environment::class),
                $store::class,
            ) extends SettingsService {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct(
                    protected Application $app,
                    protected Repository $config,
                    protected Storage $storage,
                    protected Environment $environment,
                    protected string $store,
                ) {
                    // empty
                }

                public function getStore(): string {
                    return $this->store;
                }
            };

            $this->app->bind(SettingsService::class, static function () use ($service): SettingsService {
                return $service;
            });
        }

        // Storage
        $storage = null;

        if ($content) {
            $storage = $this->app->make(ClientSettings::class);

            $storage->save($content);

            $this->app->bind(ClientSettings::class, static function () use ($storage) {
                return $storage;
            });
        }

        // Test
        $expectedResponse = is_array($expected) ? $expected['response'] : $expected;
        $expectedContent  = is_array($expected) ? $expected['content'] : null;

        $this
            ->graphQL(
                /** @lang GraphQL */
                '
                mutation updateClientSettings($settings: [UpdateClientSettingsInput!]!) {
                    updateClientSettings(input: $settings) {
                        updated {
                            name
                            value
                        }
                    }
                }',
                [
                    'settings' => $settings,
                ],
            )
            ->assertThat($expectedResponse);

        if ($expectedContent) {
            $this->assertNotNull($expectedResponse);
            $this->assertEquals($expectedContent, $storage->load());
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('updateClientSettings'),
            new RootUserDataProvider('updateClientSettings'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'updateClientSettings',
                            UpdateClientSettings::class,
                            [
                                'updated' => [
                                    [
                                        'name'  => 'a',
                                        'value' => 'a',
                                    ],
                                    [
                                        'name'  => 'b',
                                        'value' => 'b',
                                    ],
                                ],
                            ],
                        ),
                        'content'  => [
                            [
                                'name'  => 'a',
                                'value' => 'a',
                            ],
                            [
                                'name'  => 'b',
                                'value' => 'b',
                            ],
                            [
                                'name'  => 'c',
                                'value' => 'c',
                            ],
                        ],
                    ],
                    new class() {
                        #[Setting('a')]
                        #[PublicName('publicSettingA')]
                        public const A = 'test';

                        #[Setting('b')]
                        #[PublicName('publicSettingB')]
                        public const READONLY = 'readonly';
                    },
                    [
                        [
                            'name'  => 'a',
                            'value' => 'sdfsdf',
                        ],
                        [
                            'name'  => 'b',
                            'value' => 'c',
                        ],
                        [
                            'name'  => 'c',
                            'value' => 'c',
                        ],
                        [
                            'name'  => 'publicSettingB',
                            'value' => 'publicSettingB',
                        ],
                    ],
                    [
                        [
                            'name'  => 'a',
                            'value' => 'a',
                        ],
                        [
                            'name'  => 'b',
                            'value' => 'b',
                        ],
                        [
                            'name'  => 'publicSettingA',
                            'value' => 'publicSettingA',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
