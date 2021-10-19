<?php declare(strict_types = 1);

use App\Services\KeyCloak\Commands\SyncPermissions;
use Illuminate\Contracts\Console\Kernel;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        $this->sync();
    }

    protected function runRawDown(): void {
        $this->sync();
    }

    private function sync(): void {
        $kernel = $this->getContainer()->make(Kernel::class);

        $kernel->call(SyncPermissions::class);
    }
};
