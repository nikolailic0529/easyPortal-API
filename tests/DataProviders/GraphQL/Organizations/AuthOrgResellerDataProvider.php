<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use App\GraphQL\Directives\Definitions\AuthOrgResellerDirective;
use App\Models\Enums\OrganizationType;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\OrganizationProvider;

/**
 * @see AuthOrgResellerDirective
 */
class AuthOrgResellerDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'organization=null is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                new NullProvider(),
            ],
            'organization=reseller is allowed' => [
                new UnknownValue(),
                new OrganizationProvider($id, OrganizationType::reseller()),
            ],
        ]);
    }
}
