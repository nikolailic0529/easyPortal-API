<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Migrations;

use App\Services\Keycloak\Jobs\Cron\PermissionsSynchronizer;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class SyncPermissions extends RawDataMigration {
    protected function runRawUp(): void {
        $this->sync();
    }

    protected function runRawDown(): void {
        $this->sync();
    }

    private function sync(): void {
        Container::getInstance()->make(PermissionsSynchronizer::class)->dispatch();
    }
}
