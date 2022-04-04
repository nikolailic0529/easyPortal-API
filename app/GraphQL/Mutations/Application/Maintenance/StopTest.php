<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application\Maintenance;

use App\Services\Maintenance\Maintenance;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Application\Maintenance\Stop
 */
class StopTest extends TestCase {
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
    ): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($expected instanceof GraphQLSuccess) {
            $this->override(Maintenance::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('stop')
                    ->once()
                    ->andReturn(true);
            });
        } else {
            $this->override(Maintenance::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('stop')
                    ->never();
            });
        }

        $this
            ->graphQL(
                /** @lang GraphQL */
                '
                mutation stop {
                    application {
                        maintenance {
                            stop {
                                result
                            }
                        }
                    }
                }',
            )
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
                    new GraphQLSuccess(
                        'application',
                        new JsonFragmentSchema('maintenance.stop', self::class),
                        new JsonFragment('maintenance.stop', [
                            'result' => true,
                        ]),
                    ),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
