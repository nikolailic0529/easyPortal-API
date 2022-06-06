<?php declare(strict_types = 1);

use Illuminate\Contracts\Console\Kernel;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        $kernel = $this->getContainer()->make(Kernel::class);

        $kernel->call('ep:recalculator-resellers-recalculate');
        $kernel->call('ep:recalculator-customers-recalculate');
        $kernel->call('ep:recalculator-locations-recalculate');
    }
};
