<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Setting;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings as SettingsService;
use App\Services\Settings\Storage;
use App\Services\Settings\Storages\ClientSettings;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function is_array;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\UpdateClientSettings
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class UpdateClientSettingsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param Response|array{response:Response,content:array<mixed>} $expected
     * @param OrganizationFactory                                    $orgFactory
     * @param UserFactory                                            $userFactory
     * @param array<mixed>                                           $content
     * @param array<array{name:string,value:string}>                 $settings
     */
    public function testInvoke(
        Response|array $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        object $store = null,
        array $content = [],
        array $settings = [],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Service
        if ($store) {
            $service = new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Storage::class),
                $this->app->make(Environment::class),
                $store::class,
            ) extends SettingsService {
                /**
                 * @noinspection PhpMissingParentConstructorInspection
                 *
                 * @param class-string $store
                 */
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
            self::assertNotNull($expectedResponse);
            self::assertEquals($expectedContent, $storage->load());
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
            new AuthOrgRootDataProvider('updateClientSettings'),
            new AuthRootDataProvider('updateClientSettings'),
            new ArrayDataProvider([
                'ok' => [
                    [
                        'response' => new GraphQLSuccess(
                            'updateClientSettings',
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
                        #[PublicName('publicSettingA')]
                        #[Setting('a')]
                        public const A = 'test';

                        #[PublicName('publicSettingB')]
                        #[Setting('b')]
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
