<?php declare(strict_types = 1);

use App\Services\KeyCloak\Jobs\Cron\UsersSynchronizer;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        $this->getContainer()->make(UsersSynchronizer::class)->dispatch();
    }
};
