<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Organization;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 */
class OrganizationsTest extends TestCase {
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
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($prepare) {
            $prepare($this);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query {
                    organizations {
                        id
                        name
                        root
                        keycloak_scope
                    }
                    organizationsAggregated {
                        count
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
            new OrganizationUserDataProvider('organizations', [
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
