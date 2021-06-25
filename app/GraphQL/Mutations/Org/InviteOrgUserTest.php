<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Organization;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

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
        Closure $requestFactory = null,
        array $data = [
            'email'   => 'wrong@test.cpm',
            'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
        ],
    ): void {
        // Prepare
        $organization = $organizationFactory($this);

        if ($prepare) {
            $organization = $prepare($this, $organization);
        }

        $this->setUser($userFactory, $this->setOrganization($organization));

        Mail::fake();

        $requests = ['*' => Http::response(true, 201)];

        $role = null;
        if ($roleFactory) {
            $role = $roleFactory($this, $organization);
        } else {
            // Will throw validation errors without it
            $role = Role::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'name' => 'role1',
            ]);
        }


        if ($requestFactory && $role) {
            $requests = $requestFactory($this, $role);
        }
        $client = Http::fake($requests);

        $this->app->instance(Factory::class, $client);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation inviteOrgUser($input: InviteOrgUserInput!) {
                inviteOrgUser(input:$input) {
                    result
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(InviteOrganizationUser::class);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $requestFactory = static function (TestCase $test, Role $role): array {
            $client  = $test->app->make(Client::class);
            $baseUrl = $client->getBaseUrl();
            return [
                "{$baseUrl}/groups/{$role->getKey()}" => Http::response([
                    'id'   => $role->getKey(),
                    'name' => 'test',
                    'path' => '/test',
                ], 200),
                '*'                                   => Http::response(true, 200),
            ];
        };
        $roleFactory    = static function (TestCase $test, ?Organization $organization): Role {
            $input = [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'name' => 'role1',
            ];
            if ($organization) {
                $input['organization_id'] = $organization->getKey();
            }
            return Role::factory()->create($input);
        };
        $prepare        = static function (TestCase $test, ?Organization $organization): Organization {
            if ($organization && !$organization->keycloak_group_id) {
                $organization->keycloak_group_id = $test->faker->uuid();
                $organization->save();
                $organization = $organization->fresh();
            }

            return $organization;
        };
        return (new CompositeDataProvider(
            new OrganizationDataProvider('inviteOrgUser'),
            new UserDataProvider('inviteOrgUser', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok'            => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    $requestFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                ],
                'Invalid email' => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    $requestFactory,
                    [
                        'email'   => 'test',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                ],
                'Invalid role'  => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    $requestFactory,
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
