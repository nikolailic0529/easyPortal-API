<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Client;

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
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\UnknownUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Client\Settings
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
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        object $store = null,
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
        if ($settings) {
            $storage = $this->app()->make(ClientSettings::class);

            $storage->save($settings);

            $this->app->bind(ClientSettings::class, static function () use ($storage) {
                return $storage;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
            query {
                client {
                    settings {
                        name
                        value
                    }
                }
            }
            ')
            ->assertThat($expected);
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new UnknownOrgDataProvider(),
            new UnknownUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('client', [
                        'settings' => [
                            [
                                'name'  => 'ValueA',
                                'value' => '123',
                            ],
                            [
                                'name'  => 'ValueB',
                                'value' => 'asd',
                            ],
                            [
                                'name'  => 'publicSettingA',
                                'value' => 'null',
                            ],
                        ],
                    ]),
                    new class() {
                        #[PublicName('publicSettingA')]
                        #[Setting('a')]
                        public const A = 'test';

                        #[Setting('b')]
                        public const READONLY = 'readonly';
                    },
                    [
                        [
                            'name'  => 'ValueA',
                            'value' => '123',
                        ],
                        [
                            'name'  => 'ValueB',
                            'value' => 'asd',
                        ],
                        [
                            'name'  => 'publicSettingA',
                            'value' => 'asd',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
