<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
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
 * @deprecated
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\DisableUser
 */
class DisableUserTest extends TestCase {
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
        } else {
            $user  = User::factory()->create([
                'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                'type' => UserType::keycloak(),
            ]);
            $input = ['id' => $user->getKey()];
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation disableUser($input: DisableUserInput!) {
                    disableUser(input: $input) {
                        result
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);
        if ($expected instanceof GraphQLSuccess) {
            $user = User::whereKey($input['id'])->first();
            $this->assertFalse($user->enabled);
        }
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
            new OrganizationDataProvider('disableUser'),
            new OrganizationUserDataProvider('disableUser', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok-keycloak'  => [
                    new GraphQLSuccess('disableUser', DisableUser::class, [
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
                            ->shouldReceive('updateUser')
                            ->withArgs(static function (string $id, KeyCloakUser $user): bool {
                                return $id === 'd8ec7dcf-c542-42b5-8d7d-971400c02399'
                                    && $user->enabled === false;
                            })
                            ->once()
                            ->andReturn(true);
                    },
                ],
                'ok-local'     => [
                    new GraphQLSuccess('disableUser', DisableUser::class, [
                        'result' => true,
                    ]),
                    $prepare,
                    static function (): array {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::local(),
                        ]);

                        return ['id' => $user->getKey()];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
                'Invalid user' => [
                    new GraphQLError('disableUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    static function (): array {
                        return ['id' => 'd8ec7dcf-c542-42b5-8d7d-971400c02390'];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
                'own settings' => [
                    new GraphQLError('disableUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    static function (TestCase $test, Organization $organization, User $user): array {
                        return ['id' => $user->getKey()];
                    },
                    static function (MockInterface $mock): void {
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
