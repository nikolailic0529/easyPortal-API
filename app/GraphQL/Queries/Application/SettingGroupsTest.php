<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Settings\Attributes\Group as GroupAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings;
use Closure;
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

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\SettingGroups
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SettingGroupsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
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
                        settingGroups {
                            name
                            settings
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
                'ok' => [
                    new GraphQLSuccess('application', self::class, [
                        'settingGroups' => [
                            [
                                'name'     => 'Translated group name',
                                'settings' => [
                                    'SETTING_A',
                                    'SETTING_B',
                                ],
                            ],
                            [
                                'name'     => 'untranslated',
                                'settings' => [
                                    'SETTING_C',
                                ],
                            ],
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        return [
                            $locale => [
                                'settings.groups.translated' => 'Translated group name',
                            ],
                        ];
                    },
                    new class() {
                        #[SettingAttribute('test.a')]
                        #[GroupAttribute('translated')]
                        public const SETTING_A = 'a';

                        #[SettingAttribute('test.b')]
                        #[GroupAttribute('translated')]
                        public const SETTING_B = 'b';

                        #[SettingAttribute('test.c')]
                        #[GroupAttribute('untranslated')]
                        public const SETTING_C = 'c';

                        #[SettingAttribute('test.d')]
                        public const SETTING_D = 'd';

                        #[ServiceAttribute(CronJob::class, 'test.e')]
                        #[GroupAttribute('service')]
                        public const SETTING_E = 'e';

                        #[JobAttribute(Job::class, 'test.f')]
                        #[GroupAttribute('job')]
                        public const SETTING_F = 'f';
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
