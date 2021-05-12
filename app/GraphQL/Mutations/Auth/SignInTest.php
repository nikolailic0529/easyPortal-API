<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\KeyCloak\Exceptions\InvalidCredentials;
use Closure;
use Illuminate\Contracts\Hashing\Hasher;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\GuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Auth\SignIn
 */
class SignInTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array{email: string,password:string} $input
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
        array $input = [
            'email'    => '',
            'password' => '',
        ],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($prepare) {
            $prepare($this);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation signIn($input: SignInInput) {
                    signIn(input: $input) {
                        me {
                            id
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
            new AnyOrganizationDataProvider('signIn'),
            new GuestDataProvider('signIn'),
            new ArrayDataProvider([
                'no user'                           => [
                    new GraphQLError('signIn', new InvalidCredentials()),
                    static function (): void {
                        // empty
                    },
                ],
                'local user with valid password'    => [
                    new GraphQLSuccess('signIn', self::class),
                    static function (TestCase $test): void {
                        User::factory()->create([
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
                    new GraphQLError('signIn', new InvalidCredentials()),
                    static function (TestCase $test): void {
                        User::factory()->create([
                            'type'     => UserType::local(),
                            'email'    => 'test@example.com',
                            'password' => $test->app()->make(Hasher::class)->make('12345'),
                        ]);
                    },
                    [
                        'email'    => 'test@example.com',
                        'password' => 'invalid',
                    ],
                ],
                'keycloak user with valid password' => [
                    new GraphQLError('signIn', new InvalidCredentials()),
                    static function (TestCase $test): void {
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
