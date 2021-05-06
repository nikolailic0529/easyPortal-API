<?php declare(strict_types = 1);

namespace Tests\DataProviders\Http\Tenants;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\TestCase;

class TenantDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'no tenant is not allowed' => [
                new ExpectedFinal(new Forbidden()),
                static function (): ?Organization {
                    return null;
                },
            ],
            'normal tenant is allowed' => [
                new Unknown(),
                static function (TestCase $test) use ($id): ?Organization {
                    return Organization::factory()->create($id ? ['id' => $id] : []);
                },
            ],
        ]);
    }
}
