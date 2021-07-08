<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Organization;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use App\Services\KeyCloak\Client\Types\User;
use Closure;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function json_encode;
use function time;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\InviteOrgUser
 */
class InviteOrgUserTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $data
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
        Closure $roleFactory = null,
        array $data = [
            'email'   => 'wrong@test.cpm',
            'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
        ],
        Closure $clientFactory = null,
        bool $shouldSendEmail = false,
    ): void {
        // Prepare
        $organization = $organizationFactory($this);

        if ($prepare) {
            $organization = $prepare($this, $organization);
        }

        $this->setUser($userFactory, $this->setOrganization($organization));

        Mail::fake();

        if ($roleFactory) {
            $roleFactory($this, $organization);
        } else {
            // Will throw validation errors without it
            Role::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'name' => 'role1',
            ]);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation inviteOrgUser($input: InviteOrgUserInput!) {
                inviteOrgUser(input:$input) {
                    result
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $shouldSendEmail
                ? Mail::assertSent(InviteOrganizationUser::class)
                : Mail::assertNotSent(InviteOrganizationUser::class);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $roleFactory = static function (TestCase $test, ?Organization $organization): Role {
            $input = [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'name' => 'role1',
            ];
            if ($organization) {
                $input['organization_id'] = $organization->getKey();
            }
            return Role::factory()->create($input);
        };
        $prepare     = static function (TestCase $test, ?Organization $organization): Organization {
            if ($organization && !$organization->keycloak_group_id) {
                $organization->keycloak_group_id = $test->faker->uuid();
                $organization->keycloak_scope    = $test->faker->word();
                $organization->save();
                $organization = $organization->fresh();
            }

            return $organization;
        };
        return (new CompositeDataProvider(
            new OrganizationDataProvider('inviteOrgUser', '745e3dd2-915e-31b2-b02b-cbab069c9d45'),
            new UserDataProvider('inviteOrgUser', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                                  => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andReturns(true);
                    },
                    true,
                ],
                'invited/not in organization'         => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andThrow(new UserAlreadyExists('test@gmail.com'));
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'attributes' => [
                                    'ep_invite_745e3dd2-915e-31b2-b02b-cbab069c9d46' => [
                                        json_encode([
                                            'sent_at' => time(),
                                            'used_at' => time(),
                                        ]),
                                    ],
                                ],
                            ]));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once();
                    },
                    true,
                ],
                'invited/in organization used'        => [
                    new GraphQLError('inviteOrgUser', new InviteOrgUserAlreadyUsedInvitation()),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andThrow(new UserAlreadyExists('test@gmail.com'));
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'attributes' => [
                                    'ep_invite_745e3dd2-915e-31b2-b02b-cbab069c9d45' => [
                                        json_encode([
                                            'sent_at' => time(),
                                            'used_at' => time(),
                                        ]),
                                    ],
                                ],
                            ]));
                    },
                    false,
                ],
                'invited/in organization not used'    => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andThrow(new UserAlreadyExists('test@gmail.com'));
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'attributes' => [
                                    'ep_invite_745e3dd2-915e-31b2-b02b-cbab069c9d45' => [
                                        json_encode([
                                            'sent_at' => time(),
                                            'used_at' => null,
                                        ]),
                                    ],
                                ],
                            ]));
                    },
                    true,
                ],
                'un-completed/different organization' => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andThrow(new UserAlreadyExists('test@gmail.com'));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once();
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'attributes' => [
                                    'ep_invite_745e3dd2-915e-31b2-b02b-cbab069c9d46' => [
                                        json_encode([
                                            'sent_at' => null,
                                            'used_at' => null,
                                        ]),
                                    ],
                                ],
                            ]));
                    },
                    true,
                ],
                'Invalid email'                       => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                ],
                'Invalid role'                        => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
