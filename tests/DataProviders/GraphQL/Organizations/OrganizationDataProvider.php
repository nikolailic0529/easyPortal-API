<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\ExpectedFinal;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\TestCase;

class OrganizationDataProvider extends ArrayDataProvider {
    public function __construct(string $root, string $id = null) {
        parent::__construct([
            'no organization is not allowed' => [
                new ExpectedFinal(new GraphQLUnauthenticated($root)),
                static function (): ?Organization {
                    return null;
                },
            ],
            'normal organization is allowed' => [
                new Unknown(),
                static function (TestCase $test) use ($id): ?Organization {
                    return Organization::factory()->create($id ? ['id' => $id] : []);
                },
            ],
        ]);
    }
}
