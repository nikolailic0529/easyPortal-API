<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;

/**
 * Only user of current tenant can perform action.
 *
 * @see \Tests\DataProviders\GraphQL\Tenants\RootTenantDataProvider
 */
class TenantUserDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is not allowed'                    => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?User {
                    return null;
                },
            ],
            'user from root tenant is allowed'        => [
                new Unknown(),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                    ]);
                },
            ],
            'user from another tenant is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make();
                },
            ],
        ]);
    }
}
