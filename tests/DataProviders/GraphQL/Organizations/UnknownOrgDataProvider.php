<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL\Organizations;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\TestCase;

class UnknownOrgDataProvider extends ArrayDataProvider {
    public function __construct(string $id = null) {
        parent::__construct([
            'no organization is allowed' => [
                new UnknownValue(),
                static function (): ?Organization {
                    return null;
                },
            ],
            'organization is allowed'    => [
                new UnknownValue(),
                static function (TestCase $test) use ($id): Organization {
                    return Organization::factory()->create($id ? ['id' => $id] : []);
                },
            ],
        ]);
    }
}
