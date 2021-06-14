<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 */
class OrganizationsTest extends TestCase {
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
        $organization = null;

        if ($organizationFactory) {
            $organization = $organizationFactory($this);
        }

        // For current organization
        if ($organization) {
            $organization->keycloak_scope = $this->faker->word();
            $organization->save();
            $organization = $organization->fresh();
        }

        $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query {
                  organizations {
                    data {
                      id
                      name
                      root
                      keycloak_scope
                    }
                    paginatorInfo {
                      count
                      currentPage
                      firstItem
                      hasMorePages
                      lastItem
                      lastPage
                      perPage
                      total
                    }
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
            new RootOrganizationDataProvider('organizations'),
            new UserDataProvider('organizations', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated('organizations', self::class),
                    static function (): void {
                        Organization::factory()->create([
                            'keycloak_scope' => 'test',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
