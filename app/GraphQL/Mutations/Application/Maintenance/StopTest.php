<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Application\Maintenance;

use App\Services\Maintenance\Maintenance;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthRootDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Application\Maintenance\Stop
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class StopTest extends TestCase {
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
    ): void {
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

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
