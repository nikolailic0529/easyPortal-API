<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Types\Audit as TypesAudit;
use App\Models\Audits\Audit;
use App\Models\Organization;
use App\Models\User;
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
class AuditsTest extends TestCase {
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
                  audits {
                    data {
                        id
                        organization_id
                        user_id
                        object_type
                        object_id
                        context
                        action
                        created_at
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
            new RootOrganizationDataProvider('audits'),
            new OrganizationUserDataProvider('audits', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated('audits', TypesAudit::class),
                    static function (): void {
                        $user         = User::factory()->create();
                        $organization = Organization::factory()->create();
                        Audit::factory()->create([
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
                            'context'         => ['key' => 'value'],
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
