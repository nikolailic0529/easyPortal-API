<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\GuestDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Auth\SendResetPasswordLink
 */
class SendResetPasswordLinkTest extends TestCase {
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
        string $email = null,
    ): void {
        // Prepare
        $this->setRootOrganization(Organization::factory()->create());
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $user = null;

        if ($prepare) {
            $user = $prepare($this);
        }

        // Fake
        Notification::fake();

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation sendResetPasswordLink($input: SendResetPasswordLinkInput) {
                    sendResetPasswordLink(input: $input) {
                        result
                    }
                }
                GRAPHQL,
                [
                    'input' => [
                        'email' => $email ?? '',
                    ],
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess && $user) {
            Notification::assertSentTo($user, ResetPasswordNotification::class);
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
            new UnknownOrganizationDataProvider(),
            new GuestDataProvider('sendResetPasswordLink'),
            new ArrayDataProvider([
                'no user'              => [
                    new GraphQLSuccess('sendResetPasswordLink', self::class, [
                        'result' => false,
                    ]),
                    static function (): void {
                        // empty
                    },
                    'test@example.com',
                ],
                'user exists'          => [
                    new GraphQLSuccess('sendResetPasswordLink', self::class, [
                        'result' => true,
                    ]),
                    static function (): User {
                        return User::factory()->create([
                            'type'  => UserType::local(),
                            'email' => 'test@example.com',
                        ]);
                    },
                    'test@example.com',
                ],
                'keycloak user exists' => [
                    new GraphQLSuccess('sendResetPasswordLink', self::class, [
                        'result' => false,
                    ]),
                    static function (): ?User {
                        User::factory()->create([
                            'type'  => UserType::keycloak(),
                            'email' => 'test@example.com',
                        ]);

                        return null;
                    },
                    'test@example.com',
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
