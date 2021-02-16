<?php declare(strict_types = 1);

namespace Tests;

use Illuminate\Foundation\Testing\WithFaker;
use LastDragon_ru\LaraASP\Testing\Database\RefreshDatabaseIfEmpty;
use LastDragon_ru\LaraASP\Testing\TestCase as BaseTestCase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    use RefreshDatabaseIfEmpty;
    use MakesGraphQLRequests;
    use WithTenant;
    use WithUser;
    use WithFaker;
}
