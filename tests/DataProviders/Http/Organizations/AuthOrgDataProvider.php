<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Organizations;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Unauthorized;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\OrganizationProvider;

class AuthOrgDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'no organization is not allowed' => [
                new ExpectedFinal(new Unauthorized()),
                new NullProvider(),
            ],
            'normal organization is allowed' => [
                new UnknownValue(),
                new OrganizationProvider($id),
            ],
        ]);
    }
}
