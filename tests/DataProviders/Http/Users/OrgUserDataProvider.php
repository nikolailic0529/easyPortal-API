<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Users;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Unauthorized;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider as GraphQLOrgUserDataProvider;

/**
 * Only User with permission(s) can perform the action.
 */
class OrgUserDataProvider extends GraphQLOrgUserDataProvider {
    /**
     * @inheritDoc
     */
    public function __construct(array $permissions = []) {
        parent::__construct('', $permissions);
    }

    protected function getUnauthenticated(string $root): mixed {
        return new Unauthorized();
    }

    protected function getUnauthorized(string $root): mixed {
        return new Forbidden();
    }
}
