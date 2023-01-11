<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Queue\JobState;
use App\Services\Queue\Progress;
use App\Services\Queue\Queue;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Bootstrapers\LoadConfiguration;
use App\Services\Settings\Environment\Configuration;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use App\Utils\Processor\State;
use Closure;
use Config\Constants;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
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
class ServicesTest extends TestCase {
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
        Closure $queueStateFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setTranslations($translationsFactory);

        if ($store) {
            $this->override(Settings::class, function () use ($store): Settings {
                $service = new class(
                    $this->app,
                    $this->app->make(Repository::class),
                    $this->app->make(Storage::class),
                    $this->app->make(Environment::class),
                    $store::class,
                ) extends Configuration {
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

                (new class() extends LoadConfiguration {
                    /**
                     * @inheritDoc
                     */
                    public function overwriteConfig(Application $app, Repository $repository, array $config): void {
                        parent::overwriteConfig($app, $repository, $config);
                    }
                })->overwriteConfig(
                    $this->app,
                    $this->app->make(Repository::class),
                    $service->getConfiguration()['config'],
                );

                return $service;
            });
        }

        // Queue
        if ($queueStateFactory) {
            $this->override(Queue::class, function (MockInterface $mock) use ($queueStateFactory): void {
                $mock->shouldAllowMockingProtectedMethods();
                $mock->makePartial();
                $mock
                    ->shouldReceive('getStates')
                    ->once()
                    ->andReturn($queueStateFactory($this));
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
                            stoppable
                            progressable
                            state {
                                id
                                running
                                stopped
                                pending_at
                                running_at
                            }
                            progress {
                                name
                                total
                                processed
                                success
                                failed
                                updated
                                created
                                deleted
                                current
                                operations {
                                    name
                                    total
                                    processed
                                    success
                                    failed
                                    updated
                                    created
                                    deleted
                                    current
                                    operations {
                                        name
                                    }
                                }
                            }
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
                Constants::class => [
                    new GraphQLSuccess('application'),
                    null,
                    null,
                    static function (): array {
                        return [];
                    },
                ],
                'translated'     => [
                    new GraphQLSuccess('application', [
                        'services' => [
                            [
                                'name'         => 'service-a',
                                'enabled'      => true,
                                'cron'         => '*/8 * * * *',
                                'queue'        => 'queue-a',
                                'settings'     => [
                                    'SERVICE_A_ENABLED',
                                    'SERVICE_A_QUEUE',
                                    'SERVICE_A_CRON',
                                ],
                                'description'  => 'Service description.',
                                'stoppable'    => true,
                                'progressable' => false,
                                'state'        => null,
                                'progress'     => null,
                            ],
                            [
                                'name'         => 'service-b',
                                'enabled'      => true,
                                'cron'         => '* 1 1 1 *',
                                'queue'        => 'queue-b',
                                'settings'     => [
                                    'SERVICE_B_ENABLED',
                                ],
                                'description'  => 'Description description description.',
                                'stoppable'    => true,
                                'progressable' => true,
                                'state'        => [
                                    'id'         => 'a77d8197-bc62-4831-ab98-5629cb0656e7',
                                    'running'    => true,
                                    'stopped'    => false,
                                    'pending_at' => '2021-06-30T00:00:00+00:00',
                                    'running_at' => null,
                                ],
                                'progress'     => [
                                    'name'       => null,
                                    'total'      => 2,
                                    'processed'  => 1,
                                    'success'    => 1,
                                    'failed'     => 1,
                                    'updated'    => null,
                                    'created'    => null,
                                    'deleted'    => null,
                                    'current'    => null,
                                    'operations' => [
                                        [
                                            'name'       => 'A',
                                            'total'      => 100,
                                            'processed'  => 25,
                                            'success'    => 20,
                                            'failed'     => 5,
                                            'updated'    => null,
                                            'created'    => null,
                                            'deleted'    => null,
                                            'current'    => false,
                                            'operations' => null,
                                        ],
                                        [
                                            'name'       => 'B',
                                            'total'      => null,
                                            'processed'  => null,
                                            'success'    => null,
                                            'failed'     => null,
                                            'updated'    => null,
                                            'created'    => null,
                                            'deleted'    => null,
                                            'current'    => true,
                                            'operations' => null,
                                        ],
                                    ],
                                ],
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
                    static function (TestCase $test): array {
                        return [
                            'service-b' => [
                                new JobState(
                                    'service-b',
                                    'a77d8197-bc62-4831-ab98-5629cb0656e7',
                                    true,
                                    false,
                                    Date::make('2021-06-30T00:00:00+00:00'),
                                    null,
                                ),
                                new JobState(
                                    'service-b',
                                    'id-b',
                                    true,
                                    false,
                                    Date::make('2021-06-30T00:00:00+00:00'),
                                    null,
                                ),
                            ],
                        ];
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
class ServicesTest_ServiceB extends CronJob implements Progressable {
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

    public function getProgressCallback(): callable {
        return static function (): Progress {
            $state    = new State([
                'total'     => 2,
                'processed' => 1,
                'success'   => 1,
                'failed'    => 1,
            ]);
            $stateA   = new State([
                'total'     => 100,
                'processed' => 25,
                'success'   => 20,
                'failed'    => 5,
            ]);
            $stateB   = null;
            $progress = new Progress($state, null, null, [
                new Progress($stateA, 'A', false, null),
                new Progress($stateB, 'B', true, null),
            ]);

            return $progress;
        };
    }

    public function getResetProgressCallback(): callable {
        return static function (): void {
            // empty
        };
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
