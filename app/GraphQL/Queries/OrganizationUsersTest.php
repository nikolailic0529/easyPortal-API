<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization as ModelsOrganization;
use App\Models\Reseller;
use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Organization
 */
class OrganizationUsersTest extends TestCase {
    /**
     * @covers ::users
     *
     * @param array<mixed> $settings
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare

        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $client = Http::fake([
            '*' => Http::response(
                [
                    [
                        'id'                         => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                        'username'                   => 'virtualcomputersa_3@tesedi.com',
                        'enabled'                    => true,
                        'emailVerified'              => true,
                        'notBefore'                  => 0,
                        'totp'                       => false,
                        'firstName'                  => 'Reseller',
                        'lastName'                   => 'virtualcomputersa_3',
                        'email'                      => 'virtualcomputersa_3@tesedi.com',
                        'disableableCredentialTypes' => [],
                        'requiredActions'            => [],
                        'attributes'                 => [
                            'locale' => [
                                'de',
                            ],
                            'phone'  => [
                                '12345678',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);
        $this->app->instance(Factory::class, $client);
        // Test
        $this->graphQL(/** @lang GraphQL */ '{
            organization {
                users {
                    id
                    username
                    firstName
                    lastName
                    email
                    enabled
                    emailVerified
                }
            }
        }')->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new ArrayDataProvider([
                'organization' => [
                    new Unknown(),
                    static function (TestCase $test): ?ModelsOrganization {
                        $reseller     = Reseller::factory()->create();
                        $organization = ModelsOrganization::factory()
                            ->create([
                                'id'                => $reseller->getKey(),
                                'keycloak_group_id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945',
                            ]);
                        return $organization;
                    },
                ],
            ]),
            new UserDataProvider('organization', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('organization', self::class, new JsonFragment('users', [
                        [
                            'id'            => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                            'username'      => 'virtualcomputersa_3@tesedi.com',
                            'enabled'       => true,
                            'emailVerified' => true,
                            'firstName'     => 'Reseller',
                            'lastName'      => 'virtualcomputersa_3',
                            'email'         => 'virtualcomputersa_3@tesedi.com',
                        ],
                    ])),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
