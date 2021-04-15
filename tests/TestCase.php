<?php declare(strict_types = 1);

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\WithFaker;
use LastDragon_ru\LaraASP\Testing\Database\RefreshDatabaseIfEmpty;
use LastDragon_ru\LaraASP\Testing\TestCase as BaseTestCase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    use RefreshDatabaseIfEmpty;
    use MakesGraphQLRequests;
    use WithTranslations;
    use WithSettings;
    use WithTenant;
    use WithUser;
    use WithFaker {
        faker as public;
    }

    public function app(): Application {
        return $this->app;
    }
}
