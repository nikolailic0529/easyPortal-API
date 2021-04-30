<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Tenants;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\NotFound;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\TestCase;

class TenantDataProvider extends ArrayDataProvider {
    public function __construct() {
        parent::__construct([
            'no tenant' => [
                new ExpectedFinal(new NotFound()),
                static function (): ?Organization {
                    return null;
                },
            ],
            'tenant'    => [
                new Unknown(),
                static function (TestCase $test): ?Organization {
                    return Organization::factory()->create();
                },
            ],
        ]);
    }
}
