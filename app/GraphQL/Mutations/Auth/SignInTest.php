<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Keycloak\Exceptions\Auth\InvalidCredentials;
use Closure;
use Illuminate\Contracts\Hashing\Hasher;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthGuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Auth\SignIn
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SignInTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                            $orgFactory
     * @param UserFactory                                    $userFactory
     * @param OrganizationFactory                            $rootOrgFactory
     * @param Closure(static,?Organization,?User): void|null $prepare
     * @param array{email: string, password: string}|null    $input
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $rootOrgFactory = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $org  = $this->setRootOrganization(
            $this->setOrganization($orgFactory) ?? $rootOrgFactory,
        );
        $user = $this->setUser($userFactory, $org);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        $input ??= [
            'email'    => $this->faker->email(),
            'password' => $this->faker->password(8),
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation signIn($input: AuthSignInInput) {
                    auth {
                        signIn(input: $input) {
                            result
                            me {
                                id
                            }
                            org {
                                id
                            }
                        }
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
            new UnknownOrgDataProvider('99ab2b12-70f9-402e-9068-72226b808be7'),
            new AuthGuestDataProvider('auth'),
            new ArrayDataProvider([
                'no user'                           => [
                    new GraphQLError('auth', new InvalidCredentials()),
                ],
                'local user with valid password'    => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragment('signIn', [
                            'result' => true,
                            'me'     => [
                                'id' => 'e7c5d710-3524-4d98-8d67-ce8f53dd54f8',
                            ],
                            'org'    => [
                                'id' => '99ab2b12-70f9-402e-9068-72226b808be7',
                            ],
                        ]),
                    ),
                    static function (): Organization {
                        return Organization::factory()->create([
                            'id' => '99ab2b12-70f9-402e-9068-72226b808be7',
                        ]);
                    },
                    static function (TestCase $test): void {
                        User::factory()->create([
                            'id'       => 'e7c5d710-3524-4d98-8d67-ce8f53dd54f8',
                            'type'     => UserType::local(),
                            'email'    => 'test@example.com',
                            'password' => $test->app()->make(Hasher::class)->make('12345'),
                        ]);
                    },
                    [
                        'email'    => 'test@example.com',
                        'password' => '12345',
                    ],
                ],
                'local user with invalid password'  => [
                    new GraphQLError('auth', new InvalidCredentials()),
                    static function (): Organization {
                        return Organization::factory()->create([
                            'id' => 'd1fc1395-a0fc-4280-bd8a-538de37b7e13',
                        ]);
                    },
                    static function (): void {
                        User::factory()->create([
                            'type'     => UserType::local(),
                            'email'    => 'test@example.com',
                            'password' => '12345',
                        ]);
                    },
                    [
                        'email'    => 'test@example.com',
                        'password' => 'invalid',
                    ],
                ],
                'keycloak user with valid password' => [
                    new GraphQLError('auth', new InvalidCredentials()),
                    static function (): ?Organization {
                        return null;
                    },
                    static function (TestCase $test, ?Organization $organization): void {
                        User::factory()->create([
                            'type'     => UserType::keycloak(),
                            'email'    => 'test@example.com',
                            'password' => $test->app()->make(Hasher::class)->make('12345'),
                        ]);
                    },
                    [
                        'email'    => 'test@example.com',
                        'password' => '12345',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
