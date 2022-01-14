<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Closure;
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
     * @param array<string>                     $permissions
     * @param \Closure(\App\Models\User ): void $callback
     */
    public function __construct(string $root, array $permissions = [], Closure $callback = null) {
        $factory = User::factory();
        $data    = [
            'guest is not allowed' => [
                new ExpectedFinal($this->getUnauthenticated($root)),
                static function (): ?User {
                    return null;
                },
            ],
        ];

        if ($callback) {
            $factory = $factory->afterMaking($callback);
        }

        if ($permissions) {
            $data += [
                'user from another organization is not allowed'             => [
                    new ExpectedFinal($this->getUnauthorized($root)),
                    static function (TestCase $test) use ($factory, $permissions): ?User {
                        $organization = Organization::factory()->create();
                        $user         = $factory->create([
                            'organization_id' => $organization,
                            'permissions'     => $permissions,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'enabled'         => true,
                        ]);

                        return $user;
                    },
                ],
                'user without permissions from organization is not allowed' => [
                    new ExpectedFinal($this->getUnauthorized($root)),
                    static function (TestCase $test, ?Organization $organization) use ($factory): ?User {
                        $user = $factory->create([
                            'organization_id' => $organization,
                            'permissions'     => [],
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'enabled'         => true,
                        ]);

                        return $user;
                    },
                ],
                'user with permissions from organization is allowed'        => [
                    new UnknownValue(),
                    static function (
                        TestCase $test,
                        ?Organization $organization,
                    ) use (
                        $factory,
                        $permissions,
                    ): ?User {
                        $user = $factory->create([
                            'organization_id' => $organization,
                            'permissions'     => $permissions,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'enabled'         => true,
                        ]);

                        return $user;
                    },
                ],
            ];
        } else {
            $data += [
                'user from another organization is not allowed' => [
                    new ExpectedFinal($this->getUnauthorized($root)),
                    static function (TestCase $test) use ($factory): ?User {
                        $organization = Organization::factory()->create();
                        $user         = $factory->create([
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'enabled'         => true,
                        ]);

                        return $user;
                    },
                ],
                'user from organization is allowed'             => [
                    new UnknownValue(),
                    static function (TestCase $test, ?Organization $organization) use ($factory): ?User {
                        $user = $factory->create([
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'enabled'         => true,
                        ]);

                        return $user;
                    },
                ],
            ];
        }

        parent::__construct($data);
    }

    protected function getUnauthenticated(string $root): mixed {
        return new GraphQLUnauthenticated($root);
    }

    protected function getUnauthorized(string $root): mixed {
        return new GraphQLUnauthorized($root);
    }
}
