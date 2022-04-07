<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\NullProvider;
use Tests\Providers\Organizations\OrganizationProvider;

class UnknownOrgDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'no organization is allowed' => [
                new UnknownValue(),
                new NullProvider(),
            ],
            'organization is allowed'    => [
                new UnknownValue(),
                new OrganizationProvider($id),
            ],
        ]);
    }
}
