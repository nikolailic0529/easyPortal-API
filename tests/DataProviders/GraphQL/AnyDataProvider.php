<?php declare(strict_types = 1);

namespace Tests\DataProviders\GraphQL;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\Unknown;
use Tests\TestCase;

class AnyDataProvider extends ArrayDataProvider {
    public function __construct() {
        parent::__construct([
            'guest is allowed' => [
                new Unknown(),
                static function (): ?User {
                    return null;
                },
            ],
            'user is allowed'  => [
                new Unknown(),
                static function (TestCase $test, ?Organization $organization): ?User {
                    return User::factory()->make([
                        'organization_id' => $organization,
                    ]);
                },
            ],
        ]);
    }
}
