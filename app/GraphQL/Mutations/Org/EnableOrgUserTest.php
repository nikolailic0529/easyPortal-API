<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Enums\UserType;
use App\Models\Organization;
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
 * @coversDefaultClass \App\GraphQL\Mutations\Org\EnableOrgUser
 */
class EnableOrgUserTest extends TestCase {
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
        $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization);
        }

        $input = ['id' => ''];
        if ($inputFactory) {
            $input = $inputFactory($this);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation enableOrgUser($input: EnableOrgUserInput!) {
                    enableOrgUser(input: $input) {
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
            new OrganizationDataProvider('enableOrgUser'),
            new UserDataProvider('enableOrgUser', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'             => [
                    new GraphQLSuccess('enableOrgUser', EnableOrgUser::class, [
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
                    new GraphQLError('enableOrgUser', new EnableOrgUserInvalidUser()),
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
                    new GraphQLError('enableOrgUser', new UserDoesntExists()),
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
                            ->andThrow(new UserDoesntExists());
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
