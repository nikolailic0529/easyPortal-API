<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Organization;
use App\Models\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\UserProvider
 */
class UserProviderTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getProperties
     */
    public function testGetProperties(): void {
        $clientId     = $this->faker->word;
        $organization = Organization::factory()->create([
            'keycloak_scope' => $this->faker->word,
        ]);

        $keycloak = Mockery::mock(KeyCloak::class);
        $keycloak
            ->shouldReceive('getClientId')
            ->once()
            ->andReturn($clientId);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getKeyCloak')
            ->atLeast()
            ->once()
            ->andReturn($keycloak);

        $token = $this->getToken([
            'typ'                => 'Bearer',
            'resource_access'    => [
                $clientId => [
                    'roles' => [
                        'test_role_1',
                        'test_role_2',
                    ],
                ],
            ],
            'scope'              => 'openid profile email',
            'email_verified'     => true,
            'name'               => 'Tesg Test',
            'groups'             => [
                '/access-requested',
                '/resellers/reseller2',
                'offline_access',
                'uma_authorization',
            ],
            'phone_number'       => '12345678',
            'preferred_username' => 'dun00101@eoopy.com',
            'given_name'         => 'Tesg',
            'family_name'        => 'Test',
            'email'              => 'dun00101@eoopy.com',
            'reseller_access'    => [
                $organization->keycloak_scope => true,
            ],
        ]);

        $this->assertEquals([
            'email'          => 'dun00101@eoopy.com',
            'email_verified' => true,
            'given_name'     => 'Tesg',
            'family_name'    => 'Test',
            'phone'          => '12345678',
            'phone_verified' => false,
            'locale'         => null,
            'permissions'    => [
                'test_role_1',
                'test_role_2',
            ],
            'organization'   => $organization,
        ], $provider->getProperties($token));
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void {
        $token        = Mockery::mock(UnencryptedToken::class);
        $clientId     = $this->faker->word;
        $organization = Organization::factory()->create([
            'keycloak_scope' => $this->faker->word,
        ]);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getProperties')
            ->once()
            ->andReturn([
                'given_name'   => '123',
                'family_name'  => '456',
                'permissions'  => [
                    'test_role_1',
                    'test_role_2',
                ],
                'organization' => $organization,
            ]);

        // Test
        $user = User::factory()->make();

        $provider->update($user, $token);

        $this->assertEquals('123', $user->given_name);
        $this->assertEquals('456', $user->family_name);
        $this->assertEquals(['test_role_1', 'test_role_2'], $user->getPermissions());
        $this->assertEquals($organization, $user->organization);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<mixed> $claims
     */
    protected function getToken(array $claims): UnencryptedToken {
        $config  = Configuration::forUnsecuredSigner();
        $builder = $config->builder();

        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }

        return $builder->getToken($config->signer(), $config->signingKey());
    }
    //</editor-fold>
}
