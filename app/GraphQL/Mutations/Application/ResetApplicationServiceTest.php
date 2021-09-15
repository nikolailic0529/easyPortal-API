<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\ServiceNotFound;
use App\Services\Queue\Progressable;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\ResetApplicationService
 */
class ResetApplicationServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $input
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $input = ['name' => 'service'],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        // Mock
        $this->override(Settings::class, function (): Settings {
            return new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Storage::class),
                $this->app->make(Environment::class),
            ) extends Settings {
                protected function getStore(): string {
                    return (new class() {
                        #[ServiceAttribute(ResetApplicationServiceTest_ServiceA::class, 'enabled')]
                        public const SERVICE_A = true;

                        #[ServiceAttribute(ResetApplicationServiceTest_ServiceB::class, 'enabled')]
                        public const SERVICE_B = true;
                    })::class;
                }
            };
        });

        // Fake
        Queue::fake();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                mutation resetApplicationService($input: ResetApplicationServiceInput!) {
                    resetApplicationService(input: $input) {
                        result
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
            new RootOrganizationDataProvider('resetApplicationService'),
            new RootUserDataProvider('resetApplicationService'),
            new ArrayDataProvider([
                'no service'       => [
                    new GraphQLError('resetApplicationService', new ServiceNotFound('unknown-service')),
                    [
                        'name' => 'unknown-service',
                    ],
                ],
                'non-progressable' => [
                    new GraphQLSuccess('resetApplicationService', ResetApplicationService::class, [
                        'result' => false,
                    ]),
                    [
                        'name' => 'service-b',
                    ],
                ],
                'ok'               => [
                    new GraphQLSuccess('resetApplicationService', ResetApplicationService::class, [
                        'result' => true,
                    ]),
                    [
                        'name' => 'service-a',
                    ],
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
class ResetApplicationServiceTest_ServiceA extends CronJob implements Progressable {
    public function displayName(): string {
        return 'service-a';
    }

    public function getProgressCallback(): callable {
        return static function (): void {
            // empty
        };
    }

    public function getResetProgressCallback(): callable {
        return static function (): bool {
            return true;
        };
    }
}

/**
 * Service description.
 *
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ResetApplicationServiceTest_ServiceB extends CronJob {
    public function displayName(): string {
        return 'service-b';
    }
}
