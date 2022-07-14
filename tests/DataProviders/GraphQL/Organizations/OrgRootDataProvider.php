<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\Providers\Organizations\RootOrganizationProvider;

class OrgRootDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'organization=root is allowed' => [
                new UnknownValue(),
                new RootOrganizationProvider($id),
            ],
        ]);
    }
}
