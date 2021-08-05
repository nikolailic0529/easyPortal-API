<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;

/**
 * Only user of current organization can perform action.
 *
 * @see \Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider
 */
class OrganizationUserDataProvider extends ArrayDataProvider {
    /**
     * @param array<string> $permissions
     */
    public function __construct(string $root, array $permissions = []) {
        $data = [
            'guest is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?User {
                    return null;
                },
            ],
        ];

        if ($permissions) {
            $data += [
                'user from another organization is not allowed'             => [
                    new ExpectedFinal(new GraphQLUnauthorized($root)),
                    static function (TestCase $test, ?Organization $organization) use ($permissions): ?User {
                        return User::factory()->make([
                            'organization_id' => Organization::factory()->create(),
                            'permissions'     => $permissions,
                        ]);
                    },
                ],
                'user without permissions from organization is not allowed' => [
                    new ExpectedFinal(new GraphQLUnauthorized($root)),
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
                    new ExpectedFinal(new GraphQLUnauthorized($root)),
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
