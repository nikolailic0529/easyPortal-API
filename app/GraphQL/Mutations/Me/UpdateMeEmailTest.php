<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Me\UpdateMeEmail
 */
class UpdateMeEmailTest extends TestCase {
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
        string $email = '',
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

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
            $this->assertEquals($user->email, $email);
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
            new OrganizationDataProvider('updateMeEmail'),
            new AuthUserDataProvider('updateMeEmail'),
            new ArrayDataProvider([
                'keycloak'             => [
                    new GraphQLSuccess('updateMeEmail', UpdateMeEmail::class, [
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
                    new GraphQLSuccess('updateMeEmail', UpdateMeEmail::class, [
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
                    new GraphQLError('updateMeEmail', new UserAlreadyExists('new@example.com')),
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
                            ->andThrow(new UserAlreadyExists('new@example.com'));
                    },
                ],
                'local/taken'          => [
                    new GraphQLError('updateMeEmail', new UpdateMeEmailUserAlreadyExists('new@example.com')),
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