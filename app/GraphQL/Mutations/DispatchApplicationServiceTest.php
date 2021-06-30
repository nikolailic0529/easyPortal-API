<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\Queue\CronJob;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Closure;
use Exception;
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
 * @coversDefaultClass \App\GraphQL\Mutations\DispatchApplicationService
 */
class DispatchApplicationServiceTest extends TestCase {
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
        $service = new class(
            $this->app,
            $this->app->make(Repository::class),
            $this->app->make(Storage::class),
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

        // Fake
        Queue::fake();

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
            new RootOrganizationDataProvider('dispatchApplicationService'),
            new RootUserDataProvider('dispatchApplicationService'),
            new ArrayDataProvider([
                'no service' => [
                    new GraphQLError('dispatchApplicationService', new DispatchApplicationServiceNotFoundException()),
                    [
                        'name' => 'unknown-service',
                    ],
                ],
                'failed'     => [
                    new GraphQLError('dispatchApplicationService', new DispatchApplicationServiceFailed()),
                    [
                        'name'        => 'service-b',
                        'immediately' => true,
                    ],
                ],
                'ok'         => [
                    new GraphQLSuccess('dispatchApplicationService', DispatchApplicationService::class, [
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

    public function handle(): void {
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

    public function handle(): void {
        throw new Exception();
    }
}
