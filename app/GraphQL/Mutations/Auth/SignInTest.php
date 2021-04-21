<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\KeyCloak\KeyCloak;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\GuestDataProvider;
use Tests\DataProviders\TenantDataProvider;
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
     */
    public function testInvoke(Response $expected, Closure $tenantFactory, Closure $userFactory = null): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Mock
        $service = Mockery::mock(KeyCloak::class);

        if ($expected instanceof GraphQLSuccess) {
            $service
                ->shouldReceive('getAuthorizationUrl')
                ->once()
                ->andReturn('http://example.com/');
        }

        $this->app->bind(KeyCloak::class, static function () use ($service): KeyCloak {
            return $service;
        });

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation {
                signIn {
                    url
                }
            }')
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
            new TenantDataProvider(),
            new GuestDataProvider('signIn'),
            new ArrayDataProvider([
                'redirect to login' => [
                    new GraphQLSuccess('signIn', self::class, [
                        'url' => 'http://example.com/',
                    ]),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
