<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application;

use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\ServiceNotFound;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Environment\Environment;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
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
 * @covers \App\GraphQL\Mutations\Application\DispatchApplicationService
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DispatchApplicationServiceTest extends TestCase {
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
        $service = new class(
            $this->app,
            $this->app->make(Repository::class),
            $this->app->make(Dispatcher::class),
            $this->app->make(Storage::class),
            $this->app->make(Environment::class),
        ) extends Settings {
            protected function getStore(): string {
                return (new class() {
                    #[ServiceAttribute(DispatchApplicationServiceTest_ServiceA::class, 'enabled')]
                    public const SERVICE_A = true;

                    #[ServiceAttribute(DispatchApplicationServiceTest_ServiceB::class, 'enabled')]
                    public const SERVICE_B = true;
                })::class;
            }
        };

        $this->app->bind(Settings::class, static function () use ($service): Settings {
            return $service;
        });

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                mutation DispatchApplicationService($input: DispatchApplicationServiceInput!) {
                    dispatchApplicationService(input: $input) {
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
            new AuthOrgRootDataProvider('dispatchApplicationService'),
            new AuthRootDataProvider('dispatchApplicationService'),
            new ArrayDataProvider([
                'no service' => [
                    new GraphQLError('dispatchApplicationService', new ServiceNotFound('unknown-service')),
                    [
                        'name' => 'unknown-service',
                    ],
                ],
                'failed'     => [
                    new GraphQLError('dispatchApplicationService', new Exception('An unknown error occurred.')),
                    [
                        'name'        => 'service-b',
                        'immediately' => true,
                    ],
                ],
                'ok'         => [
                    new GraphQLSuccess('dispatchApplicationService', [
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
class DispatchApplicationServiceTest_ServiceA extends CronJob {
    public function displayName(): string {
        return 'service-a';
    }

    public function __invoke(): void {
        // no action
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class DispatchApplicationServiceTest_ServiceB extends CronJob {
    public function displayName(): string {
        return 'service-b';
    }

    public function __invoke(): void {
        throw new Exception();
    }
}
