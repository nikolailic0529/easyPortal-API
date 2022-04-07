<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\GraphQL\Directives\Definitions\AuthRootDirective;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\Providers\Users\GuestUserProvider;
use Tests\Providers\Users\RootUserProvider;
use Tests\Providers\Users\UserProvider;

/**
 * Only root cat perform the action.
 *
 * @see AuthRootDirective
 */
class AuthRootDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is not allowed'  => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                new GuestUserProvider(),
            ],
            'user is not allowed'   => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                new UserProvider(),
            ],
            'local root is allowed' => [
                new UnknownValue(),
                new RootUserProvider(),
            ],
        ]);
    }
}
