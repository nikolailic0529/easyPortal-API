<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserSearch;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\UserSearches
 */
class UserSearchesTest extends TestCase {
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $userSearchesFactory = null,
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $key = 'wrong';
        if ($user && $userSearchesFactory) {
            $key = $userSearchesFactory($this, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query searches ($key: String!){
                    me {
                        searches(key: $key) {
                            id
                            key
                            name
                            conditions
                            user_id
                        }
                    }
            }', ['key' => $key])
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
            new TenantDataProvider(),
            new ArrayDataProvider([
                // Guest not allowed will return null from Me ( already tested in Me )
                'user is allowed' => [
                    new Unknown(),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->create([
                            'organization_id' => $organization,
                            'id'              => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                        ]);
                    },
                ],
            ]),
            new ArrayDataProvider([
                'match' => [
                    new GraphQLSuccess('me', UserSearches::class, [
                        'searches' => [
                            [
                                'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'key'        => 'key',
                                'name'       => 'saved_filter',
                                'conditions' => 'conditions',
                                'user_id'    => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            ],
                        ],
                    ]),
                    static function (TestCase $test, User $user): string {
                        UserSearch::factory([
                            'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'key'        => 'key',
                            'name'       => 'saved_filter',
                            'conditions' => 'conditions',
                            'user_id'    => $user->id,
                        ])->create();
                        return 'key';
                    },
                ],
                'empty' => [
                    new GraphQLSuccess('me', UserSearches::class, [
                        'searches' => [],
                    ]),
                    static function (TestCase $test, User $user): string {
                        UserSearch::factory([
                            'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'key'        => 'key',
                            'name'       => 'saved_filter',
                            'conditions' => 'conditions',
                            'user_id'    => $user->id,
                        ])->create();
                        return 'wrong_key';
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
