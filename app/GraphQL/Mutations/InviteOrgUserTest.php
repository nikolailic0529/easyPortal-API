<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\InviteOrganizationUser;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\InviteOrgUser
 */
class InviteOrgUserTest extends TestCase {
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
        array $data = [
            'email' => '',
            'role'  => '',
        ],
    ): void {
        // Prepare
        $organization = $organizationFactory($this);

        if ($prepare) {
            $organization = $prepare($this, $organization);
        }

        $this->setUser($userFactory, $this->setOrganization($organization));

        Mail::fake();

        $client = Http::fake([
            '*' => Http::response(true, 201),
        ]);
        $this->app->instance(Factory::class, $client);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation inviteOrgUser($input: inviteOrgUserInput!) {
                inviteOrgUser(input:$input) {
                    result
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(InviteOrganizationUser::class);
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
            new OrganizationDataProvider('inviteOrgUser'),
            new UserDataProvider('inviteOrgUser', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    static function (TestCase $test, Organization $organization): Organization {
                        if ($organization && !$organization->keycloak_group_id) {
                            $organization->keycloak_group_id = $test->faker->uuid();
                            $organization->save();
                            $organization = $organization->fresh();
                        }

                        return $organization;
                    },
                    [
                        'email' => 'test@gmail.com',
                        'role'  => 'view-quotes',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
