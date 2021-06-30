<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Jobs\NamedJob;
use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Bootstraper;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Closure;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
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
 * @coversDefaultClass \App\GraphQL\Queries\Application\Settings
 */
class ServicesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $translationsFactory = null,
        object $store = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        if ($store) {
            $service = new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Storage::class),
                $store::class,
            ) extends Bootstraper {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct(
                    protected Application $app,
                    protected Repository $config,
                    protected Storage $storage,
                    protected string $store,
                ) {
                    // empty
                }

                public function getStore(): string {
                    return $this->store;
                }

                protected function isBootstrapped(): bool {
                    return false;
                }
            };

            $service->bootstrap();

            $this->app->bind(Settings::class, static function () use ($service): Settings {
                return $service;
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    application {
                        services {
                            name
                            enabled
                            cron
                            queue
                            settings
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
            new RootOrganizationDataProvider('application'),
            new RootUserDataProvider('application'),
            new ArrayDataProvider([
                Constants::class => [
                    new GraphQLSuccess('application', self::class),
                ],
                'translated'     => [
                    new GraphQLSuccess('application', self::class, [
                        'services' => [
                            [
                                'name'        => 'service-a',
                                'enabled'     => true,
                                'cron'        => '*/8 * * * *',
                                'queue'       => 'queue-a',
                                'settings'    => [
                                    'SERVICE_A_ENABLED',
                                    'SERVICE_A_QUEUE',
                                    'SERVICE_A_CRON',
                                ],
                                'description' => 'Service description.',
                            ],
                            [
                                'name'        => 'service-b',
                                'enabled'     => true,
                                'cron'        => '* 1 1 1 *',
                                'queue'       => 'queue-b',
                                'settings'    => [
                                    'SERVICE_B_ENABLED',
                                ],
                                'description' => 'Description description description.',
                            ],
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        return [
                            $locale => [
                                'settings.services.service-b' => 'Description description description.',
                            ],
                        ];
                    },
                    new class() {
                        // Standard settings should be ignored
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

                        // Service
                        #[ServiceAttribute(ServicesTest_ServiceA::class, 'enabled')]
                        public const SERVICE_A_ENABLED = true;

                        #[ServiceAttribute(ServicesTest_ServiceA::class, 'queue')]
                        public const SERVICE_A_QUEUE = 'queue-a';

                        #[ServiceAttribute(ServicesTest_ServiceA::class, 'cron')]
                        public const SERVICE_A_CRON = '*/8 * * * *';

                        // Partial missed (shouldn't throw an error)
                        #[ServiceAttribute(ServicesTest_ServiceB::class, 'enabled')]
                        public const SERVICE_B_ENABLED = true;

                        // Job
                        #[JobAttribute(ServicesTest_Job::class, 'enabled')]
                        public const JOB = true;
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
 * Service description.
 *
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServicesTest_ServiceA extends CronJob {
    public function displayName(): string {
        return 'service-a';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServicesTest_ServiceB extends CronJob implements NamedJob {
    public function displayName(): string {
        return 'service-b';
    }

    /**
     * @inheritDoc
     */
    public function getQueueConfig(): array {
        return [
                'enabled' => false,
                'cron'    => '* 1 1 1 *',
                'queue'   => 'queue-b',
            ] + parent::getQueueConfig();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServicesTest_Job extends Job {
    public function displayName(): string {
        return 'job';
    }
}
