<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Exceptions\RealmUserAlreadyExists;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthMeDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Me\UpdateMeEmail
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class UpdateMeEmailTest extends TestCase {
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
        string $email = '',
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Lighthouse performs validation BEFORE permission check :(
        //
        // https://github.com/nuwave/lighthouse/issues/1780
        //
        // Following code required to "fix" it
        $email = $email ?: 'test@example.com';
        $input = [
            'email' => $email,
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation updateMeEmail($input: UpdateMeEmailInput!) {
                    updateMeEmail(input: $input) {
                        result
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess && $user->type === UserType::local()) {
            self::assertEquals($user->email, $email);
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
            new AuthOrgDataProvider('updateMeEmail'),
            new AuthMeDataProvider('updateMeEmail'),
            new ArrayDataProvider([
                'keycloak'             => [
                    new GraphQLSuccess('updateMeEmail', [
                        'result' => true,
                    ]),
                    static function (TestCase $test, ?Organization $organization, ?User $user): bool {
                        $user->email = 'old@example.com';
                        $user->type  = UserType::keycloak();
                        $user->save();

                        return true;
                    },
                    'new@example.com',
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUserEmail')
                            ->once();
                    },
                ],
                'local'                => [
                    new GraphQLSuccess('updateMeEmail', [
                        'result' => true,
                    ]),
                    static function (TestCase $test, ?Organization $organization, ?User $user): bool {
                        $user->email = 'old@example.com';
                        $user->type  = UserType::local();
                        $user->save();

                        return true;
                    },
                    'new@example.com',
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUserEmail')
                            ->never();
                    },
                ],
                'keycloak/email taken' => [
                    new GraphQLError('updateMeEmail', new RealmUserAlreadyExists('new@example.com')),
                    static function (TestCase $test, ?Organization $organization, ?User $user): bool {
                        $user->email = 'old@example.com';
                        $user->type  = UserType::keycloak();
                        $user->save();

                        return true;
                    },
                    'new@example.com',
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUserEmail')
                            ->once()
                            ->andThrow(new RealmUserAlreadyExists('new@example.com'));
                    },
                ],
                'keycloak/taken'       => [
                    new GraphQLError('updateMeEmail', static function (): array {
                        return [trans('errors.validation_failed')];
                    }),
                    static function (TestCase $test, ?Organization $organization, ?User $user): bool {
                        $user->email = 'old@example.com';
                        $user->type  = UserType::keycloak();
                        $user->save();

                        User::factory(['email' => 'new@example.com'])->create();

                        return true;
                    },
                    'new@example.com',
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUserEmail')
                            ->never();
                    },
                ],
                'local/taken'          => [
                    new GraphQLError('updateMeEmail', static function (): array {
                        return [trans('errors.validation_failed')];
                    }),
                    static function (TestCase $test, ?Organization $organization, ?User $user): bool {
                        $user->email = 'old@example.com';
                        $user->type  = UserType::local();
                        $user->save();

                        User::factory(['email' => 'new@example.com'])->create();

                        return true;
                    },
                    'new@example.com',
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUserEmail')
                            ->never();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
