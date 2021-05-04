<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Tenants;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\TestCase;

class RootTenantDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'no tenant'   => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?Organization {
                    return null;
                },
            ],
            'root tenant' => [
                new Unknown(),
                static function (TestCase $test) use ($id): ?Organization {
                    return Organization::factory()->root()->create($id ? ['id' => $id] : []);
                },
            ],
        ]);
    }
}
