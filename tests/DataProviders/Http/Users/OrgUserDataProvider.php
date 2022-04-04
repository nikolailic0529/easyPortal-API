<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Users;

use Closure;
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
    public function __construct(array $permissions = [], Closure $callback = null) {
        parent::__construct('', $permissions, $callback);
    }

    protected function getUnauthenticated(string $root): mixed {
        return new Unauthorized();
    }

    protected function getUnauthorized(string $root): mixed {
        return new Forbidden();
    }
}
