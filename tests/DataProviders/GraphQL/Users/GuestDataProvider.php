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
 * Only Guest cat perform the action.
 */
class GuestDataProvider extends ArrayDataProvider {
    public function __construct(string $root) {
        parent::__construct([
            'guest is allowed'    => [
                new UnknownValue(),
                static function (): ?User {
                    return null;
                },
            ],
            'user is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->create([
                        'organization_id' => $organization,
                    ]);
                },
            ],
        ]);
    }
}
