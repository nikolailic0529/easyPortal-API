<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Migrations;

use App\Services\KeyCloak\Jobs\Cron\PermissionsSynchronizer;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class SyncPermissions extends RawDataMigration {
    protected function runRawUp(): void {
        $this->sync();
    }

    protected function runRawDown(): void {
        $this->sync();
    }

    private function sync(): void {
        $this->getContainer()->make(PermissionsSynchronizer::class)->dispatch();
    }
}
