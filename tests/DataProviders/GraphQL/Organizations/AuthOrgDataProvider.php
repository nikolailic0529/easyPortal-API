<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use App\GraphQL\Directives\Definitions\AuthOrgDirective;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\ResellerOrganizationProvider;
use Tests\Providers\Users\RootUserProvider;

/**
 * @see AuthOrgDirective
 */
class AuthOrgDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'organization=null is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthorized($root)),
                new NullProvider(),
                new RootUserProvider(),
            ],
            'organization=reseller is allowed' => [
                new UnknownValue(),
                new ResellerOrganizationProvider($id),
            ],
        ]);
    }
}
