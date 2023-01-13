<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Auth;

use App\Models\Invitation as InvitationModel;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthGuestDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Auth\Invitation
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class InvitationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                           $orgFactory
     * @param UserFactory                                   $userFactory
     * @param Closure(static, ?Organization, ?User): string $factory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $org   = $this->setOrganization($orgFactory);
        $user  = $this->setUser($userFactory, $org);
        $token = $factory ? $factory($this, $org, $user) : $this->faker->uuid();

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query test($token: String!){
                    auth {
                        invitation(token: $token) {
                            outdated
                            expired
                            used
                            org {
                                id
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'token' => $token,
                ],
            )
            ->assertThat($expected);
    }

    public function testGetInvitation(): void {
        $query      = $this->app->make(Invitation::class);
        $encrypter  = $this->app->make(Encrypter::class);
        $invitation = InvitationModel::factory()->create();

        self::assertNull($query->getInvitation('invalid token'));
        self::assertNull($query->getInvitation($encrypter->encrypt([
            'invitation' => 'unknown invitation',
        ])));
        self::assertEquals($invitation, $query->getInvitation($encrypter->encrypt([
            'invitation' => $invitation->getKey(),
        ])));
    }

    public function testOutdated(): void {
        $query = $this->app->make(Invitation::class);
        $org   = Organization::factory()->create();
        $user  = User::factory()->create();
        $a     = InvitationModel::factory()->ownedBy($org)->create([
            'user_id'    => $user,
            'created_at' => Date::now()->subDay(),
        ]);
        $b     = InvitationModel::factory()->ownedBy($org)->create([
            'user_id' => $user,
        ]);

        self::assertTrue($query->isOutdated($a));
        self::assertFalse($query->isOutdated($b));
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
            new AuthGuestDataProvider('auth'),
            new ArrayDataProvider([
                'ok'            => [
                    new GraphQLSuccess('auth', [
                        'invitation' => [
                            'org'      => [
                                'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                            ],
                            'used'     => false,
                            'expired'  => false,
                            'outdated' => false,
                        ],
                    ]),
                    static function (self $test): string {
                        $organization = Organization::factory()->create([
                            'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                        ]);
                        $user         = User::factory()->create();
                        $invitation   = InvitationModel::factory()->ownedBy($organization)->create([
                            'user_id' => $user,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return $test->app->make(Encrypter::class)->encrypt([
                            'invitation' => $invitation->getKey(),
                        ]);
                    },
                ],
                'invalid token' => [
                    new GraphQLSuccess('auth', [
                        'invitation' => null,
                    ]),
                    static function (self $test): string {
                        return $test->faker->sentence();
                    },
                ],
                'not found'     => [
                    new GraphQLSuccess('auth', [
                        'invitation' => null,
                    ]),
                    static function (self $test): string {
                        return $test->app->make(Encrypter::class)->encrypt([
                            'invitation' => $test->faker->uuid(),
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
