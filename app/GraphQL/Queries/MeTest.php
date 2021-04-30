<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\User;
use App\Models\UserSearch;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

// FIXME [Test] We should standard User DataProviders here.

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Me
 */
class MeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @covers ::root
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $settings = null,
    ): void {
        $this->setUser($userFactory, $this->setTenant($tenantFactory));
        $this->setSettings($settings);

        $this
            ->graphQL(/** @lang GraphQL */ '{
                me {
                    id
                    family_name
                    given_name
                    locale
                    root
                }
            }')
            ->assertThat($expected);
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderSearches
     */
    public function testSearches(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $userSearchesFactory = null,
    ): void {
        // Prepare
        $user = $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $key = 'wrong';

        if ($userSearchesFactory) {
            $key = $userSearchesFactory($this, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query searches ($key: String!){
                    me {
                        searches(where: {key: {eq: $key}}) {
                            id
                            key
                            name
                            conditions
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
                'guest is allowed'           => [
                    new GraphQLSuccess('me', null),
                    static function (): ?User {
                        return null;
                    },
                ],
                'user is allowed (not root)' => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment('root', false)),
                    static function (): ?User {
                        return User::factory()->make();
                    },
                ],
                'user is allowed (root)'     => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment('root', true)),
                    static function (): ?User {
                        return User::factory()->make([
                            'id' => '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        ]);
                    },
                    [
                        'ep.root_users' => [
                            '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderSearches(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new ArrayDataProvider([
                'guest is allowed' => [
                    new GraphQLSuccess('me', self::class, 'null'),
                    static function (): ?User {
                        return null;
                    },
                    static function (TestCase $test, ?User $user): string {
                        return 'key';
                    },
                ],
                'user is allowed'  => [
                    new GraphQLSuccess('me', self::class, new JsonFragment('searches', [
                        [
                            'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'key'        => 'key',
                            'name'       => 'saved_filter',
                            'conditions' => 'conditions',
                        ],
                    ])),
                    static function (): ?User {
                        return User::factory()->create();
                    },
                    static function (TestCase $test, ?User $user): string {
                        if ($user) {
                            UserSearch::factory([
                                'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'key'        => 'key',
                                'name'       => 'saved_filter',
                                'conditions' => 'conditions',
                                'user_id'    => $user->id,
                            ])->create();
                        }

                        return 'key';
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
