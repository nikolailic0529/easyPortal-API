<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\User;
use App\Services\KeyCloak\KeyCloak;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\GuestDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

// FIXME [tests] We need to check the full structure of the response but it is
//      blocked by the method of how the used library loads relative $ref
//      (relative $ref always resolves based on the top-schema path instead of
//      path of the schema where $ref defined).

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Auth\Authorize
 */
class AuthorizeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Mock
        $code    = $this->faker->word;
        $state   = $this->faker->word;
        $user    = User::factory()->make();
        $service = Mockery::mock(KeyCloak::class);

        if ($expected instanceof GraphQLSuccess) {
            $service
                ->shouldReceive('authorize')
                ->with($code, $state)
                ->once()
                ->andReturn($user);
        }

        $this->app->bind(KeyCloak::class, static function () use ($service): KeyCloak {
            return $service;
        });

        // Test
        $this
            ->graphQL(
                /** @lang GraphQL */
                'mutation authorize($input: AuthorizeInput!) {
                    authorize(input: $input) {
                        me {
                            id
                            family_name
                            given_name
                            locale
                            root
                        }
                    }
                }',
                [
                    'input' => [
                        'code'  => $code,
                        'state' => $state,
                    ],
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
            new TenantDataProvider('authorize'),
            new GuestDataProvider('authorize'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('authorize', self::class),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
