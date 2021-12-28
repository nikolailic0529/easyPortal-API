<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;

/**
 * Only root cat perform the action.
 */
class RootUserDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is not allowed'  => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?User {
                    return null;
                },
            ],
            'user is not allowed'   => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->create([
                        'type'            => UserType::keycloak(),
                        'organization_id' => $organization,
                    ]);
                },
            ],
            'local root is allowed' => [
                new UnknownValue(),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->create([
                        'type'            => UserType::local(),
                        'organization_id' => $organization,
                    ]);
                },
            ],
        ]);
    }
}
