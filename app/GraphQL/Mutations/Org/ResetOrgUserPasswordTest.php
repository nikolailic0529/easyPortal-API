<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\Group;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Org\ResetOrgUserPassword
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ResetOrgUserPasswordTest extends TestCase {
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
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $org = $this->setOrganization($orgFactory);

        $this->setUser($userFactory, $org);

        if ($org) {
            $org->keycloak_group_id = 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985';
            $org->save();
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation ResetOrgUserPassword($input: ResetOrgUserPasswordInput!) {
                resetOrgUserPassword(input:$input) {
                    result
                }
            }', ['input' => ['id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982']])
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
            new AuthOrgDataProvider('resetOrgUserPassword', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983'),
            new OrgUserDataProvider('resetOrgUserPassword', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'           => [
                    new GraphQLSuccess('resetOrgUserPassword', [
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
