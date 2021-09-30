<?php declare(strict_types = 1);

use Illuminate\Contracts\Console\Kernel;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        $kernel = $this->getContainer()->make(Kernel::class);

        $kernel->call('ep:data-loader-recalculate-resellers');
        $kernel->call('ep:data-loader-recalculate-customers');
        $kernel->call('ep:data-loader-recalculate-locations');
    }
};
