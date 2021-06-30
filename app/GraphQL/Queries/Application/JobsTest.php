<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Jobs\NamedJob;
use App\Services\Queue\CronJob;
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
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Application\Jobs
 */
class JobsTest extends TestCase {
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
                        jobs {
                            name
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
                        'jobs' => [
                            [
                                'name'        => JobsTest_JobA::class,
                                'queue'       => 'queue-a',
                                'settings'    => [
                                    'JOB_A_QUEUE',
                                ],
                                'description' => 'Job description.',
                            ],
                            [
                                'name'        => 'job-b',
                                'queue'       => 'queue-b',
                                'settings'    => [
                                    'JOB_B_QUEUE',
                                ],
                                'description' => 'Description description description.',
                            ],
                        ],
                    ]),
                    static function (TestCase $test, string $locale): array {
                        return [
                            $locale => [
                                'settings.jobs.job-b' => 'Description description description.',
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

                        // Jobs
                        #[JobAttribute(JobsTest_JobA::class, 'queue')]
                        public const JOB_A_QUEUE = 'queue-a';

                        #[JobAttribute(JobsTest_JobB::class, 'queue')]
                        public const JOB_B_QUEUE = 'queue-b';

                        // Service
                        #[ServiceAttribute(JobsTest_Service::class, 'enabled')]
                        public const SERVICE = true;
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
 * Job description.
 *
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JobsTest_JobA extends Job {
    // empty
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JobsTest_JobB extends Job implements NamedJob {
    public function displayName(): string {
        return 'job-b';
    }

    /**
     * @inheritDoc
     */
    public function getQueueConfig(): array {
        return [
                'queue' => 'queue-b',
            ] + parent::getQueueConfig();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JobsTest_Service extends CronJob {
    public function displayName(): string {
        return 'test-cron-job';
    }
}
