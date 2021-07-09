<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserDoesntExists;
use App\Services\KeyCloak\Client\Types\Group;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\EnableOrganizationUser
 */
class EnableOrganizationUserTest extends TestCase {
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
        Closure $inputFactory = null,
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $this->setUser($userFactory, $organization);

        if ($organization) {
            $organization->keycloak_group_id = 'd8ec7dcf-c542-42b5-8d7d-971400c02388';
            $organization->save();
        }
        $id = '';
        if ($inputFactory) {
            $id = $inputFactory($this);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        $input = [
            'id' => $id,
        ];
        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation enableOrganizationUser($input: EnableOrganizationUserInput!) {
                    enableOrganizationUser(input: $input) {
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
        return (new CompositeDataProvider(
            new OrganizationDataProvider('enableOrganizationUser'),
            new UserDataProvider('enableOrganizationUser', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('enableOrganizationUser', EnableOrganizationUser::class, [
                        'result' => true,
                    ]),
                    static function (): string {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::keycloak(),
                        ]);
                        return $user->getKey();
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
                    new GraphQLError('enableOrganizationUser', new EnableOrganizationUserInvalidUser()),
                    static function (): string {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::keycloak(),
                        ]);
                        return $user->getKey();
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
                    new GraphQLError('enableOrganizationUser', new UserDoesntExists()),
                    static function (): string {
                        $user = User::factory()->create([
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02399',
                            'type' => UserType::keycloak(),
                        ]);
                        return $user->getKey();
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserGroups')
                            ->with('d8ec7dcf-c542-42b5-8d7d-971400c02399')
                            ->once()
                            ->andThrow(new UserDoesntExists());
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
