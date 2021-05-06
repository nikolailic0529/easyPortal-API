<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Organization;
use App\Services\KeyCloak\KeyCloak;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Tenants\AnyTenantDataProvider;
use Tests\DataProviders\GraphQL\Users\GuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

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
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $organizationFactory = null,
    ): void {
        $this->markTestSkipped('Temporary disabled because https://github.com/nuwave/lighthouse/issues/1780');

        // Prepare
        $tenant = $this->setTenant($tenantFactory);
        $user   = $this->setUser($userFactory, $tenant);

        // Organization
        $id = $this->faker->uuid;

        if ($organizationFactory) {
            $organization = $organizationFactory($this, $tenant, $user);

            if ($organization) {
                $id = $organization->getKey();
            }
        }

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
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation signin($id: ID!) {
                    signIn(input: {organization_id: $id}) {
                        url
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
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
            new AnyTenantDataProvider('signIn'),
            new GuestDataProvider('signIn'),
            new ArrayDataProvider([
                'organization not exists' => [
                    new GraphQLError('signIn', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function () {
                        return null;
                    },
                ],
                'redirect to login'       => [
                    new GraphQLSuccess('signIn', self::class, [
                        'url' => 'http://example.com/',
                    ]),
                    static function () {
                        return Organization::factory()->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
