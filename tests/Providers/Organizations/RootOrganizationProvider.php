<?php declare(strict_types = 1);

namespace Tests\Providers\Organizations;

use App\Models\Organization;
use Tests\TestCase;

class RootOrganizationProvider extends OrganizationProvider {
    public function __invoke(TestCase $test): Organization {
        return $test->setRootOrganization(parent::__invoke($test));
    }
}
