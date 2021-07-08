<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\ResetOrgUserPassword
 */
class ResetOrgUserPasswordTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $this->setUser($userFactory, $organization);

        if ($organization) {
            $organization->keycloak_group_id = 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985';
            $organization->save();
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation ResetOrgUserPassword($input: ResetOrgUserPasswordInput!) {
                resetOrgUserPassword(input:$input) {
                    result
                }
            }', ['input' => ['id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982' ]])
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
            new OrganizationDataProvider('resetOrgUserPassword', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983'),
            new UserDataProvider('resetOrgUserPassword', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'           => [
                    new GraphQLSuccess('resetOrgUserPassword', ResetOrgUserPassword::class, [
                        'result' => true,
                    ]),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24982')
                            ->once()
                            ->andReturns([
                                new Group(['id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985']),
                            ]);
                        $mock
                            ->shouldReceive('requestResetPassword')
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24982')
                            ->once();
                    },
                ],
                'Invalid user' => [
                    new GraphQLError('resetOrgUserPassword', new ResetOrgUserPasswordInvalidUser()),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24982')
                            ->once()
                            ->andReturns([
                                new Group(['id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986']),
                            ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
