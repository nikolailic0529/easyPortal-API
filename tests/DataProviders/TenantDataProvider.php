<?php declare(strict_types = 1);

namespace Tests\DataProviders;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\NotFoundResponse;
use Tests\TestCase;

class TenantDataProvider extends ArrayDataProvider {
    public function __construct() {
        parent::__construct([
            'no tenant' => [
                new ExpectedFinal(new NotFoundResponse()),
                static function (): ?Organization {
                    return null;
                },
            ],
            'tenant'    => [
                new Unknown(),
                static function (TestCase $test): ?Organization {
                    return Organization::factory()->create([
                        'subdomain' => $test->faker()->word,
                    ]);
                },
            ],
        ]);
    }
}
