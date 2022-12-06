<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Audits\Audit;
use App\Models\Data\Type;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class AuditsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                              $orgFactory
     * @param UserFactory                                      $userFactory
     * @param Closure(static, ?Organization, ?User): void|null $prepare
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
                    audits {
                        id
                        organization_id
                        user_id
                        object_type
                        object_id
                        object {
                            ... on UserOrganization {
                                organization_id
                                __typename
                            }
                            ... on ChangeRequest {
                                id
                                __typename
                            }
                            ... on QuoteRequest {
                                id
                                __typename
                            }
                            ... on Organization {
                                id
                                __typename
                            }
                            ... on Invitation {
                                id
                                __typename
                            }
                            ... on User {
                                id
                                __typename
                            }
                            ... on Role {
                                id
                                __typename
                            }
                            ... on Unknown {
                                id
                                type
                                __typename
                            }
                        }
                        context
                        action
                    }
                    auditsAggregated {
                        count
                        groups(groupBy: {user_id: asc}) {
                            key
                            count
                        }
                        groupsAggregated(groupBy: {user_id: asc}) {
                            count
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
            new AuthOrgRootDataProvider('audits'),
            new OrgUserDataProvider('audits', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated(
                        'audits',
                        [
                            [
                                'id'              => '7004c31f-5ab7-4109-b03e-415f85c7702b',
                                'user_id'         => '616ae5d8-3ef5-4603-ae1f-dfdb3d8d1929',
                                'organization_id' => '6ad1293a-ba5c-459e-9b14-5dbbd09b415f',
                                'action'          => 'AuthFailed',
                                'context'         => 'null',
                                'object'          => [
                                    'id'         => '20a03c94-3f5b-46e5-8356-f0989f7aaf82',
                                    'type'       => 'Type',
                                    '__typename' => 'Unknown',
                                ],
                                'object_id'       => '20a03c94-3f5b-46e5-8356-f0989f7aaf82',
                                'object_type'     => 'Type',
                            ],
                            [
                                'id'              => '80aff983-7fa5-468c-94ca-22b91ec6b23f',
                                'user_id'         => '616ae5d8-3ef5-4603-ae1f-dfdb3d8d1929',
                                'organization_id' => null,
                                'action'          => 'AuthSignedIn',
                                'context'         => '{"key":"value"}',
                                'object'          => [
                                    'id'         => '616ae5d8-3ef5-4603-ae1f-dfdb3d8d1929',
                                    '__typename' => 'User',
                                ],
                                'object_id'       => '616ae5d8-3ef5-4603-ae1f-dfdb3d8d1929',
                                'object_type'     => 'User',
                            ],
                        ],
                        [
                            'count'            => 2,
                            'groups'           => [
                                [
                                    'key'   => '616ae5d8-3ef5-4603-ae1f-dfdb3d8d1929',
                                    'count' => 2,
                                ],
                            ],
                            'groupsAggregated' => [
                                'count' => 1,
                            ],
                        ],
                    ),
                    static function (): void {
                        $org  = Organization::factory()->create([
                            'id' => '6ad1293a-ba5c-459e-9b14-5dbbd09b415f',
                        ]);
                        $user = User::factory()->create([
                            'id' => '616ae5d8-3ef5-4603-ae1f-dfdb3d8d1929',
                        ]);
                        $type = Type::factory()->create([
                            'id' => '20a03c94-3f5b-46e5-8356-f0989f7aaf82',
                        ]);

                        Audit::factory()->ownedBy($org)->create([
                            'id'          => '7004c31f-5ab7-4109-b03e-415f85c7702b',
                            'action'      => Action::authFailed(),
                            'user_id'     => $user,
                            'context'     => null,
                            'object_type' => $type->getMorphClass(),
                            'object_id'   => $type->getKey(),
                        ]);
                        Audit::factory()->create([
                            'id'          => '80aff983-7fa5-468c-94ca-22b91ec6b23f',
                            'action'      => Action::authSignedIn(),
                            'user_id'     => $user,
                            'context'     => ['key' => 'value'],
                            'object_type' => $user->getMorphClass(),
                            'object_id'   => $user->getKey(),
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
