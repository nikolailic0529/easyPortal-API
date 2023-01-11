<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\PasswordReset;
use App\Models\User;
use Closure;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Event;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthGuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Auth\ResetPassword
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ResetPasswordTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
        Closure $inputFactory = null,
    ): void {
        // Prepare
        $this->setRootOrganization(Organization::factory()->create());
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $success = null;
        $input   = [
            'email'    => $this->faker->email(),
            'token'    => $this->faker->sha256(),
            'password' => $this->faker->password(8),
        ];

        if ($prepare) {
            $success = $prepare($this);
        }

        if ($inputFactory) {
            $input = $inputFactory($this);
        }

        // Fake
        Event::fake([PasswordResetEvent::class]);

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation resetPassword($input: ResetPasswordInput) {
                    resetPassword(input: $input) {
                        result
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess && $success) {
            Event::assertDispatched(PasswordResetEvent::class);
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
            new UnknownOrgDataProvider(),
            new AuthGuestDataProvider('resetPassword'),
            new ArrayDataProvider([
                'no user'                              => [
                    new GraphQLSuccess('resetPassword', [
                        'result' => false,
                    ]),
                    static function (): bool {
                        return false;
                    },
                    static function () {
                        return [
                            'email'    => 'test@example.com',
                            'token'    => '12345678',
                            'password' => '12345678',
                        ];
                    },
                ],
                'invalid token'                        => [
                    new GraphQLSuccess('resetPassword', [
                        'result' => false,
                    ]),
                    static function (): bool {
                        User::factory()->create([
                            'type'  => UserType::local(),
                            'email' => 'test@example.com',
                        ]);
                        PasswordReset::factory()->create([
                            'email' => 'test@example.com',
                            'token' => 'invalid',
                        ]);

                        return false;
                    },
                    static function () {
                        return [
                            'email'    => 'test@example.com',
                            'token'    => '12345678',
                            'password' => '12345678',
                        ];
                    },
                ],
                'user exists and token valid'          => [
                    new GraphQLSuccess('resetPassword', [
                        'result' => true,
                    ]),
                    static function (TestCase $test): bool {
                        User::factory()->create([
                            'type'  => UserType::local(),
                            'email' => 'test@example.com',
                        ]);
                        PasswordReset::factory()->create([
                            'email' => 'test@example.com',
                            'token' => $test->app->make(Hasher::class)->make('12345678'),
                        ]);

                        return true;
                    },
                    static function () {
                        return [
                            'email'    => 'test@example.com',
                            'token'    => '12345678',
                            'password' => '12345678',
                        ];
                    },
                ],
                'keycloak user exists and token valid' => [
                    new GraphQLSuccess('resetPassword', [
                        'result' => false,
                    ]),
                    static function (TestCase $test): bool {
                        User::factory()->create([
                            'type'  => UserType::keycloak(),
                            'email' => 'test@example.com',
                        ]);
                        PasswordReset::factory()->create([
                            'email' => 'test@example.com',
                            'token' => $test->app->make(Hasher::class)->make('12345678'),
                        ]);

                        return false;
                    },
                    static function () {
                        return [
                            'email'    => 'test@example.com',
                            'token'    => '12345678',
                            'password' => '12345678',
                        ];
                    },
                ],
                'same password'                        => [
                    new GraphQLError('resetPassword', new ResetPasswordSamePasswordException()),
                    static function (TestCase $test): bool {
                        User::factory()->create([
                            'type'     => UserType::local(),
                            'email'    => 'test@example.com',
                            'password' => $test->app->make(Hasher::class)->make('12345678'),
                        ]);
                        PasswordReset::factory()->create([
                            'email' => 'test@example.com',
                            'token' => $test->app->make(Hasher::class)->make('12345678'),
                        ]);

                        return false;
                    },
                    static function () {
                        return [
                            'email'    => 'test@example.com',
                            'token'    => '12345678',
                            'password' => '12345678',
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
