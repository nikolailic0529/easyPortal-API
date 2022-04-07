<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use App\GraphQL\Directives\Definitions\AuthOrgRootDirective;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\OrganizationProvider;
use Tests\Providers\Organizations\RootOrganizationProvider;

/**
 * @see AuthOrgRootDirective
 */
class AuthOrgRootDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'no organization is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                new NullProvider(),
            ],
            'organization is not allowed'    => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                new OrganizationProvider(),
            ],
            'root organization is allowed'   => [
                new UnknownValue(),
                new RootOrganizationProvider($id),
            ],
        ]);
    }
}
