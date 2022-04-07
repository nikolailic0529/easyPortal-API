<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Users;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\Providers\Users\GuestUserProvider;
use Tests\Providers\Users\OrgUserProvider;
use Tests\TestCase;

/**
 * Only user of current organization can perform action.
 */
class OrgUserDataProvider extends ArrayDataProvider {
    /**
     * @param array<string> $permissions
     */
    public function __construct(string $root, array $permissions = [], string $id = null) {
        $data = [
            'guest is not allowed' => [
                new ExpectedFinal($this->getUnauthenticated($root)),
                new GuestUserProvider(),
            ],
        ];

        if ($permissions) {
            $data += [
                'user from another organization is not allowed'             => [
                    new ExpectedFinal($this->getUnauthorized($root)),
                    new class($id, $permissions) extends OrgUserProvider {
                        public function __invoke(TestCase $test, ?Organization $organization): User {
                            return parent::__invoke($test, Organization::factory()->create());
                        }
                    },
                ],
                'user without permissions from organization is not allowed' => [
                    new ExpectedFinal($this->getUnauthorized($root)),
                    new OrgUserProvider($id),
                ],
                'user with permissions from organization is allowed'        => [
                    new UnknownValue(),
                    new OrgUserProvider($id, $permissions),
                ],
            ];
        } else {
            $data += [
                'user from another organization is not allowed' => [
                    new ExpectedFinal($this->getUnauthorized($root)),
                    new class($id) extends OrgUserProvider {
                        public function __invoke(TestCase $test, ?Organization $organization): User {
                            return parent::__invoke($test, Organization::factory()->create());
                        }
                    },
                ],
                'user from organization is allowed'             => [
                    new UnknownValue(),
                    new OrgUserProvider($id),
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
