<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\RootUserDataProvider;
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
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

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
            new OrganizationDataProvider('organizations'),
            new RootUserDataProvider('organizations'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated('organizations', self::class),
                    static function (): void {
                        Organization::factory()->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
