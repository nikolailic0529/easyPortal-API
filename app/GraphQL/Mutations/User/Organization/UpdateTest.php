<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User\Organization;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

use function array_combine;
use function array_keys;
use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\User\Organization\Update
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $clientFactory = null,
        Closure $inputUserFactory = null,
        Closure $inputOrganizationFactory = null,
        Closure $inputFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Input
        $input = [
            'userId'         => $inputUserFactory
                ? $inputUserFactory($this, $organization, $user)->getKey()
                : $this->faker->uuid,
            'organizationId' => $inputOrganizationFactory
                ? $inputOrganizationFactory($this, $organization, $user)->getKey()
                : $this->faker->uuid,
            'input'          => $inputFactory
                ? $inputFactory($this, $organization, $user)
                : [],
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation updateUser($userId: ID!, $organizationId: ID!, $input: UserOrganizationUpdateInput!) {
                    user(id: $userId) {
                        organization(id: $organizationId) {
                            update(input: $input) {
                                result
                                organization {
                                    organization_id
                                    role {
                                        id
                                        name
                                    }
                                    team {
                                        id
                                        name
                                    }
                                    enabled
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                $input,
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            /** @var \App\Models\User $updated */
            $updated    = OrganizationUser::query()
                ->where('user_id', '=', $input['userId'])
                ->where('organization_id', '=', $input['organizationId'])
                ->firstOrFail();
            $expected   = $input['input'];
            $attributes = array_keys($expected);
            $values     = array_map(static fn(string $attr) => $updated->getAttribute($attr), $attributes);
            $actual     = array_combine($attributes, $values);

            $this->assertEquals($expected, $actual);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('user'),
            new OrganizationUserDataProvider('user', [
                'administer',
            ]),
            new ArrayDataProvider([
                'All possible properties'                   => [
                    new GraphQLSuccess(
                        'user',
                        new JsonFragmentSchema('organization.update', self::class),
                        new JsonFragment('organization.update', [
                            'result'       => true,
                            'organization' => [
                                'organization_id' => '6c04e3d9-4677-4ed5-a174-2a255aadab2c',
                                'role'            => [
                                    'id'   => '7f29f131-bd8a-41f5-a4d6-98e8e3aa95a7',
                                    'name' => 'Role',
                                ],
                                'team'            => [
                                    'id'   => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                                    'name' => 'Team',
                                ],
                                'enabled'         => true,
                            ],
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser());
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->once()
                            ->andReturn(true);
                    },
                    static function (): User {
                        return User::factory()->create([
                            'id' => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                        ]);
                    },
                    static function (): Organization {
                        $role         = Role::factory()->create([
                            'id' => '1a6445f4-49bd-4291-b1ec-7633a361f083',
                        ]);
                        $team         = Team::factory()->create([
                            'id' => '5bd8ea94-a471-4207-9a66-926da602db46',
                        ]);
                        $organization = Organization::factory()->create([
                            'id' => '6c04e3d9-4677-4ed5-a174-2a255aadab2c',
                        ]);

                        Role::factory()->create([
                            'id'              => '7f29f131-bd8a-41f5-a4d6-98e8e3aa95a7',
                            'name'            => 'Role',
                            'organization_id' => $organization,
                        ]);
                        Team::factory()->create([
                            'id'   => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                            'name' => 'Team',
                        ]);

                        OrganizationUser::factory()->create([
                            'id'              => '6c904820-4fbb-409b-97e5-e4ea9e6d966b',
                            'organization_id' => $organization,
                            'user_id'         => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                            'role_id'         => $role,
                            'team_id'         => $team,
                            'enabled'         => false,
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            'enabled' => true,
                            'role_id' => '7f29f131-bd8a-41f5-a4d6-98e8e3aa95a7',
                            'team_id' => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                        ];
                    },
                ],
                'Empty properties'                          => [
                    new GraphQLSuccess(
                        'user',
                        new JsonFragmentSchema('organization.update', self::class),
                        new JsonFragment('organization.update', [
                            'result'       => true,
                            'organization' => [
                                'organization_id' => 'c3aec6b9-5e9b-4c0b-b38f-31d5af303638',
                                'role'            => null,
                                'team'            => null,
                                'enabled'         => false,
                            ],
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        // empty
                    },
                    static function (): User {
                        return User::factory()->create([
                            'id' => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                        ]);
                    },
                    static function (): Organization {
                        $organization = Organization::factory()->create([
                            'id' => 'c3aec6b9-5e9b-4c0b-b38f-31d5af303638',
                        ]);

                        OrganizationUser::factory()->create([
                            'id'              => '6c904820-4fbb-409b-97e5-e4ea9e6d966b',
                            'organization_id' => $organization,
                            'user_id'         => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                            'role_id'         => null,
                            'team_id'         => null,
                            'enabled'         => false,
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            // empty
                        ];
                    },
                ],
                'No unnecessary `removeUserFromGroup` call' => [
                    new GraphQLSuccess(
                        'user',
                        new JsonFragmentSchema('organization.update', self::class),
                        new JsonFragment('organization.update.result', true),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser());
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->never();
                    },
                    static function (): User {
                        return User::factory()->create([
                            'id' => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                        ]);
                    },
                    static function (): Organization {
                        $organization = Organization::factory()->create([
                            'id' => '6c04e3d9-4677-4ed5-a174-2a255aadab2c',
                        ]);

                        Role::factory()->create([
                            'id'              => '7f29f131-bd8a-41f5-a4d6-98e8e3aa95a7',
                            'name'            => 'Role',
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'id'              => '6c904820-4fbb-409b-97e5-e4ea9e6d966b',
                            'organization_id' => $organization,
                            'user_id'         => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                            'role_id'         => null,
                            'enabled'         => false,
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            'role_id' => '7f29f131-bd8a-41f5-a4d6-98e8e3aa95a7',
                        ];
                    },
                ],
                'User not found'                            => [
                    new GraphQLError('user', static function (): Throwable {
                        return new ObjectNotFound((new User())->getMorphClass());
                    }),
                    null,
                    static function (): User {
                        return User::factory()->make();
                    },
                    static function (): Organization {
                        return Organization::factory()->make();
                    },
                    static function (): array {
                        return [
                            'enabled' => true,
                        ];
                    },
                ],
                'User doesn\'t belong to the organization'  => [
                    new GraphQLError('user', static function (): Throwable {
                        return new ObjectNotFound((new OrganizationUser())->getMorphClass());
                    }),
                    null,
                    static function (): User {
                        return User::factory()->create();
                    },
                    static function (): Organization {
                        return Organization::factory()->create();
                    },
                    static function (): array {
                        return [
                            'enabled' => true,
                        ];
                    },
                ],
                'Me is not allowed'                         => [
                    new GraphQLValidationError('user'),
                    null,
                    static function (self $test, Organization $organization, User $user): User {
                        return $user;
                    },
                    static function (self $test, Organization $organization, User $user): Organization {
                        $organization = Organization::factory()->create();

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            'enabled' => true,
                        ];
                    },
                ],
                'Role belongs to another organization'      => [
                    new GraphQLValidationError('user'),
                    null,
                    static function (): User {
                        return User::factory()->create([
                            'id' => 'd47a1cd2-9fa8-45c0-9593-90d65d5b0a19',
                        ]);
                    },
                    static function (): Organization {
                        $organization = Organization::factory()->create();

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => 'd47a1cd2-9fa8-45c0-9593-90d65d5b0a19',
                        ]);

                        Role::factory()->create([
                            'id'              => '347a96c0-97e4-49d6-b6f7-7ae24538e2e0',
                            'organization_id' => Organization::factory()->create(),
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            'role_id' => '347a96c0-97e4-49d6-b6f7-7ae24538e2e0',
                        ];
                    },
                ],
                'Root cannot be updated by user'            => [
                    new GraphQLUnauthorized('user'),
                    null,
                    static function (): User {
                        return User::factory()->create([
                            'id'   => 'd47a1cd2-9fa8-45c0-9593-90d65d5b0a19',
                            'type' => UserType::local(),
                        ]);
                    },
                    static function (): Organization {
                        $organization = Organization::factory()->create();

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => 'd47a1cd2-9fa8-45c0-9593-90d65d5b0a19',
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            'enabled' => false,
                        ];
                    },
                ],
                'Root can be updated by root'               => [
                    new GraphQLSuccess(
                        'user',
                        new JsonFragmentSchema('organization.update', self::class),
                        new JsonFragment('organization.update.result', true),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser());
                    },
                    static function (self $test, Organization $organization, User $user): User {
                        $user->type = UserType::local();
                        $user->save();

                        return User::factory()->create([
                            'id'   => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                            'type' => UserType::local(),
                        ]);
                    },
                    static function (): Organization {
                        $organization = Organization::factory()->create();

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => '1c97ad0b-d36e-4564-91ba-676a4e741bad',
                        ]);

                        return $organization;
                    },
                    static function (): array {
                        return [
                            'enabled' => false,
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
