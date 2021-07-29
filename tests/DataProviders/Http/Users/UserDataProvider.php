<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Users;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Unauthorized;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use LengthException;
use Tests\TestCase;

use function count;

/**
 * Only User with permission(s) can perform the action.
 */
class UserDataProvider extends ArrayDataProvider {
    /**
     * @param array<string> $permissions
     */
    public function __construct(array $permissions) {
        if (!$permissions) {
            throw new LengthException('Permissions cannot be empty.');
        }

        parent::__construct([
            'guest is not allowed'                    => [
                new ExpectedFinal(new Unauthorized()),
                static function (): ?User {
                    return null;
                },
            ],
            'user without permissions is not allowed' => [
                new ExpectedFinal(new Forbidden()),
                static function (TestCase $test, ?Organization $organization) use ($permissions): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                        'permissions'     => count($permissions) > 1
                            ? $test->faker()->randomElements($permissions, count($permissions) - 1)
                            : [],
                    ]);
                },
            ],
            'user with permissions is allowed'        => [
                new UnknownValue(),
                static function (TestCase $test, ?Organization $organization) use ($permissions): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                        'permissions'     => $permissions,
                    ]);
                },
            ],
        ]);
    }
}
