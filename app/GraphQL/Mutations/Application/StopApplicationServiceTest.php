<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\ServiceNotFound;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Queue;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Application\StopApplicationService
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class StopApplicationServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed>        $input
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $input = ['name' => 'service'],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Mock
        $this->override(Settings::class, function (): Settings {
            return new class(
                $this->app,
                $this->app->make(Repository::class),
                $this->app->make(Dispatcher::class),
                $this->app->make(Storage::class),
                $this->app->make(Environment::class),
            ) extends Settings {
                protected function getStore(): string {
                    return (new class() {
                        #[ServiceAttribute(StopApplicationServiceTest_ServiceA::class, 'enabled')]
                        public const SERVICE_A = true;

                        #[ServiceAttribute(StopApplicationServiceTest_ServiceB::class, 'enabled')]
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
                mutation stopApplicationService($input: StopApplicationServiceInput!) {
                    stopApplicationService(input: $input) {
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
            new AuthOrgRootDataProvider('stopApplicationService'),
            new AuthRootDataProvider('stopApplicationService'),
            new ArrayDataProvider([
                'no service' => [
                    new GraphQLError('stopApplicationService', new ServiceNotFound('unknown-service')),
                    [
                        'name' => 'unknown-service',
                    ],
                ],
                'ok'         => [
                    new GraphQLSuccess('stopApplicationService', [
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
class StopApplicationServiceTest_ServiceA extends CronJob {
    public function displayName(): string {
        return 'service-a';
    }
}


/**
 * Service description.
 *
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class StopApplicationServiceTest_ServiceB extends CronJob {
    public function displayName(): string {
        return 'service-b';
    }
}
