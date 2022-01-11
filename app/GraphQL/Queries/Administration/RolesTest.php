<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Organization;
use App\Models\Role;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Administration\Users
 */
class RolesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization, $user);
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
            new RootOrganizationDataProvider('roles'),
            new OrganizationUserDataProvider('roles', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('roles', self::class, [
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
