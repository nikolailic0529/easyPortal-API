<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Bootstrapers\LoadConfiguration;
use App\Services\Settings\Environment\Configuration;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Closure;
use Config\Constants;
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
 * @covers \App\GraphQL\Queries\Application\Jobs
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class JobsTest extends TestCase {
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
                $this->app->make(Storage::class),
                $this->app->make(Environment::class),
                $store::class,
            ) extends Configuration {
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
            new AuthOrgRootDataProvider('application'),
            new AuthRootDataProvider('application'),
            new ArrayDataProvider([
                Constants::class => [
                    new GraphQLSuccess('application'),
                ],
                'translated'     => [
                    new GraphQLSuccess('application', [
                        'jobs' => [
                            [
                                'name'        => 'job-a',
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
    public function displayName(): string {
        return 'job-a';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class JobsTest_JobB extends Job {
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
        return 'service';
    }
}
