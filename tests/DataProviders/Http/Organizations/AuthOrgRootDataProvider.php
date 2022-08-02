<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Organizations;

use App\GraphQL\Directives\Definitions\AuthOrgRootDirective;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\OrganizationProvider;
use Tests\Providers\Organizations\RootOrganizationProvider;
use Tests\Providers\Users\RootUserProvider;

/**
 * @see AuthOrgRootDirective
 */
class AuthOrgRootDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'no organization is not allowed' => [
                new ExpectedFinal(new Forbidden()),
                new NullProvider(),
                new RootUserProvider(),
            ],
            'organization is not allowed'    => [
                new ExpectedFinal(new Forbidden()),
                new OrganizationProvider($id),
                new RootUserProvider(),
            ],
            'root organization is allowed'   => [
                new UnknownValue(),
                new RootOrganizationProvider($id),
            ],
        ]);
    }
}
