<?php

namespace Tests;

use LastDragon_ru\LaraASP\Testing\Database\RefreshDatabaseIfEmpty;
use LastDragon_ru\LaraASP\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    use RefreshDatabaseIfEmpty;
}
