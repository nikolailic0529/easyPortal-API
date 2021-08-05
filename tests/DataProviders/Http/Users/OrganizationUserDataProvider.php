<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Users;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Unauthorized;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\TestCase;

/**
 * Only User with permission(s) can perform the action.
 */
class OrganizationUserDataProvider extends ArrayDataProvider {
    /**
     * @param array<string> $permissions
     */
    public function __construct(array $permissions = []) {
        $data = [
            'guest is not allowed' => [
                new ExpectedFinal(new Unauthorized()),
                static function (): ?User {
                    return null;
                },
            ],
        ];

        if ($permissions) {
            $data += [
                'user from another organization is not allowed'             => [
                    new ExpectedFinal(new Forbidden()),
                    static function (TestCase $test, ?Organization $organization) use ($permissions): ?User {
                        return User::factory()->make([
                            'organization_id' => Organization::factory()->create(),
                            'permissions'     => $permissions,
                        ]);
                    },
                ],
                'user without permissions from organization is not allowed' => [
                    new ExpectedFinal(new Forbidden()),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                            'permissions'     => [],
                        ]);
                    },
                ],
                'user with permissions from organization is allowed'        => [
                    new UnknownValue(),
                    static function (TestCase $test, ?Organization $organization) use ($permissions): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                            'permissions'     => $permissions,
                        ]);
                    },
                ],
            ];
        } else {
            $data += [
                'user from another organization is not allowed' => [
                    new ExpectedFinal(new Forbidden()),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => Organization::factory()->create(),
                        ]);
                    },
                ],
                'user from organization is allowed'             => [
                    new UnknownValue(),
                    static function (TestCase $test, ?Organization $organization): ?User {
                        return User::factory()->make([
                            'organization_id' => $organization,
                        ]);
                    },
                ],
            ];
        }

        parent::__construct($data);
    }
}
