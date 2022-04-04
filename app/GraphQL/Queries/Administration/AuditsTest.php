<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\GraphQL\Types\Audit as TypesAudit;
use App\Models\Audits\Audit;
use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 */
class AuditsTest extends TestCase {
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
                    audits {
                        id
                        organization_id
                        user_id
                        object_type
                        object_id
                        context
                        action
                        created_at
                    }
                    auditsAggregated {
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
            new AuthOrgRootDataProvider('audits'),
            new OrgUserDataProvider('audits', [
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
