<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\GraphQL\Mutations\Auth\ResetPasswordSamePasswordException;
use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use Closure;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Event;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Me\UpdateMePassword
 */
class UpdateMePasswordTest extends TestCase {
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
        Closure $prepare = null,
        Closure $inputFactory = null,
        Closure $clientFactory = null,
        bool $isRootOrganization = false,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($isRootOrganization) {
            $this->setRootOrganization($organization);
        }
        $input = [
            'password'         => '',
            'current_password' => '',
        ];

        if ($prepare) {
            $prepare($this, $user);
        }

        if ($inputFactory) {
            $input = $inputFactory($this);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Fake
        Event::fake([PasswordResetEvent::class]);

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation updateMePassword($input: UpdateMePasswordInput!) {
                    updateMePassword(input: $input) {
                        result
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess && $isRootOrganization) {
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
            new AuthOrgDataProvider('updateMePassword'),
            new AuthMeDataProvider('updateMePassword'),
            new ArrayDataProvider([
                'keycloak user'                       => [
                    new GraphQLSuccess('updateMePassword', UpdateMePassword::class, [
                        'result' => true,
                    ]),
                    static function (TestCase $test, User $user): bool {
                        $user->type = UserType::keycloak();
                        $user->save();
                        return true;
                    },
                    static function () {
                        return [
                            'password' => '12345678',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('resetPassword')
                            ->once()
                            ->andReturn(true);
                    },
                    false,
                ],
                'local user'                          => [
                    new GraphQLSuccess('updateMePassword', UpdateMePassword::class, [
                        'result' => true,
                    ]),
                    static function (TestCase $test, User $user): bool {
                        $user->email    = 'test@gmail.com';
                        $user->type     = UserType::local();
                        $user->password = $test->app->make(Hasher::class)->make('12345678');
                        $user->save();
                        return true;
                    },
                    static function () {
                        return [
                            'current_password' => '12345678',
                            'password'         => '12345687',
                        ];
                    },
                    null,
                    true,
                ],
                'local user/Invalid current password' => [
                    new GraphQLError('updateMePassword', new UpdateMePasswordInvalidCurrentPassword()),
                    static function (TestCase $test, User $user): bool {
                        $user->email    = 'test@gmail.com';
                        $user->type     = UserType::local();
                        $user->password = $test->app->make(Hasher::class)->make('12345678');
                        $user->save();
                        return true;
                    },
                    static function () {
                        return [
                            'current_password' => '12345679',
                            'password'         => '12345687',
                        ];
                    },
                    null,
                    true,
                ],
                'local user/same password'            => [
                    new GraphQLError('updateMePassword', new ResetPasswordSamePasswordException()),
                    static function (TestCase $test, User $user): bool {
                        $user->email    = 'test@gmail.com';
                        $user->type     = UserType::local();
                        $user->password = $test->app->make(Hasher::class)->make('12345678');
                        $user->save();
                        return true;
                    },
                    static function () {
                        return [
                            'current_password' => '12345678',
                            'password'         => '12345678',
                        ];
                    },
                    null,
                    true,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
