<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\TestCase;

/**
 * Any authenticated User can perform the action.
 */
class AuthUserDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?User {
                    return null;
                },
            ],
            'user is allowed'      => [
                new UnknownValue(),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                    ]);
                },
            ],
        ]);
    }
}
