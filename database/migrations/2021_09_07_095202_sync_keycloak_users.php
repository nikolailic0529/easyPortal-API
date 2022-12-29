<?php declare(strict_types = 1);

use App\Services\Keycloak\Jobs\Cron\UsersSynchronizer;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        Container::getInstance()->make(UsersSynchronizer::class)->dispatch();
    }
};
