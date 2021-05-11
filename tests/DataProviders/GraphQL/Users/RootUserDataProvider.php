<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;

/**
 * Only root cat perform the action.
 *
 * @see \Config\Constants::EP_ROOT_USERS
 */
class RootUserDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is not allowed'         => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?User {
                    return null;
                },
            ],
            'user is not allowed'          => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                    ]);
                },
            ],
            'keycloak root is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                static function (TestCase $test, ?Organization $organization): ?User {
                    $user = User::factory()->make();

                    $test->app()->make(Repository::class)->set(
                        'ep.root_users',
                        $user->getKey(),
                    );

                    return $user;
                },
            ],
            'local root is allowed'        => [
                new Unknown(),
                static function (TestCase $test, ?Organization $organization): ?User {
                    $user = User::factory()->make([
                        'type' => UserType::local(),
                    ]);

                    $test->app()->make(Repository::class)->set(
                        'ep.root_users',
                        $user->getKey(),
                    );

                    return $user;
                },
            ],
        ]);
    }
}
