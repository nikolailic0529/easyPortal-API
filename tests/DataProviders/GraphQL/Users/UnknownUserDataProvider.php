<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\Users\GuestUserProvider;
use Tests\Providers\Users\UserProvider;

/**
 * Any guest or any user can perform action.
 */
class UnknownUserDataProvider extends ArrayDataProvider {
    public function __construct() {
        parent::__construct([
            'guest is allowed' => [
                new UnknownValue(),
                new GuestUserProvider(),
            ],
            'user is allowed'  => [
                new UnknownValue(),
                new UserProvider(),
            ],
        ]);
    }
}
