<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound;
use App\Services\KeyCloak\Client\Types\Group;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\DisableOrgUser
 */
class DisableOrgUserTest extends TestCase {
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
        Closure $inputFactory = null,
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization);
        }

        $input = ['id' => ''];
        if ($inputFactory) {
            $input = $inputFactory($this, $organization, $user);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation disableOrgUser($input: DisableOrgUserInput!) {
                    disableOrgUser(input: $input) {
                        result
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare = static function (TestCase $test, ?Organization $organization): void {
            if ($organization) {
                $organization->keycloak_group_id = 'd8ec7dcf-c542-42b5-8d7d-971400c02388';
                $organization->save();
            }
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider('disableOrgUser'),
            new OrganizationUserDataProvider('disableOrgUser', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('disableOrgUser', DisableOrgUser::class, [
                        'result' => true,
                    ]),
                    $prepare,
                    static function (): array {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::keycloak(),
                        ]);

                        return ['id' => $user->getKey()];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->with('d8ec7dcf-c542-42b5-8d7d-971400c02399')
                            ->once()
                            ->andReturn([
                                new Group([
                                    'id' => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                                ]),
                            ]);
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                    },
                ],
                'invalid user'   => [
                    new GraphQLError('disableOrgUser', new DisableOrgUserInvalidUser()),
                    $prepare,
                    static function (): array {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::keycloak(),
                        ]);

                        return ['id' => $user->getKey()];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->with('d8ec7dcf-c542-42b5-8d7d-971400c02399')
                            ->once()
                            ->andReturn([
                                new Group([
                                    'id' => 'd8ec7dcf-c542-42b5-8d7d-971400c02377',
                                ]),
                            ]);
                    },
                ],
                'user not found' => [
                    new GraphQLError('disableOrgUser', new RealmUserNotFound('d8ec7dcf-c542-42b5-8d7d-971400c02399')),
                    $prepare,
                    static function (): array {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::keycloak(),
                        ]);

                        return ['id' => $user->getKey()];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->with('d8ec7dcf-c542-42b5-8d7d-971400c02399')
                            ->once()
                            ->andThrow(new RealmUserNotFound('d8ec7dcf-c542-42b5-8d7d-971400c02399'));
                    },
                ],
                'own settings'   => [
                    new GraphQLError('disableOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    static function (TestCase $test, Organization $organization, User $user): array {
                        return ['id' => $user->getKey()];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->never();

                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
