<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\GraphQL\Directives\Definitions\AuthMeDirective;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\Providers\Users\GuestUserProvider;
use Tests\Providers\Users\UserProvider;

/**
 * Any authenticated User can perform the action.
 *
 * @see AuthMeDirective
 */
class AuthMeDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'guest is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                new GuestUserProvider(),
            ],
            'user is allowed'      => [
                new UnknownValue(),
                new UserProvider($id),
            ],
        ]);
    }
}
