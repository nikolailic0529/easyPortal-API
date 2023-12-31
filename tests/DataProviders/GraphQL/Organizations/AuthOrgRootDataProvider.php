<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use App\GraphQL\Directives\Definitions\AuthOrgRootDirective;
use App\Models\Enums\OrganizationType;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\OrganizationProvider;
use Tests\Providers\Organizations\RootOrganizationProvider;
use Tests\Providers\Users\RootUserProvider;

/**
 * @see AuthOrgRootDirective
 */
class AuthOrgRootDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null, OrganizationType $type = null) {
        parent::__construct([
            'organization=null is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                new NullProvider(),
                new RootUserProvider(),
            ],
            'organization=any is not allowed'  => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                new OrganizationProvider(),
                new RootUserProvider(),
            ],
            'organization=root is allowed'     => [
                new UnknownValue(),
                new RootOrganizationProvider($id, $type),
            ],
        ]);
    }
}
