<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\GraphQL\Directives\Definitions\AuthGuestDirective;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\Providers\Users\GuestUserProvider;
use Tests\Providers\Users\UserProvider;

/**
 * Only Guest cat perform the action.
 *
 * @see AuthGuestDirective
 */
class AuthGuestDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is allowed'    => [
                new UnknownValue(),
                new GuestUserProvider(),
            ],
            'user is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                new UserProvider(),
            ],
        ]);
    }
}
