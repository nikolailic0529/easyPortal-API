<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Organization;
use App\Models\Role;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Administration\Users
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class RolesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query {
                    roles {
                        id
                        name
                    }
                }
            ')->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('roles'),
            new OrgUserDataProvider('roles', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('roles', [
                        [
                            'id'   => '3a75a9a4-9943-441f-b123-ffbd885249df',
                            'name' => 'Role',
                        ],
                    ]),
                    static function (self $test, Organization $organization): void {
                        Role::factory()->create([
                            'id'              => '3a75a9a4-9943-441f-b123-ffbd885249df',
                            'name'            => 'Role',
                            'organization_id' => null,
                        ]);
                        Role::factory()->create([
                            'organization_id' => $organization,
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
