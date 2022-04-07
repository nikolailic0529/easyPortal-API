<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Organizations;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Unauthorized;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\ResellerOrganizationProvider;

class AuthOrgDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'organization=null is not allowed' => [
                new ExpectedFinal(new Unauthorized()),
                new NullProvider(),
            ],
            'organization=reseller is allowed' => [
                new UnknownValue(),
                new ResellerOrganizationProvider($id),
            ],
        ]);
    }
}
