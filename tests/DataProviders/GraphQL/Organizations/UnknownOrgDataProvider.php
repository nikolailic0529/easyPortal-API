<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\ResellerOrganizationProvider;

class UnknownOrgDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'organization=null is allowed'     => [
                new UnknownValue(),
                new NullProvider(),
            ],
            'organization=reseller is allowed' => [
                new UnknownValue(),
                new ResellerOrganizationProvider($id),
            ],
        ]);
    }
}
