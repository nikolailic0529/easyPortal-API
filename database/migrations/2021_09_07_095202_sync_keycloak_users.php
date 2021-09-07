<?php declare(strict_types = 1);

use App\Services\KeyCloak\Jobs\SyncUsersCronJob;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        $this->getContainer()->make(SyncUsersCronJob::class)->dispatch();
    }
};
