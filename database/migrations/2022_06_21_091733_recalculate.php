<?php declare(strict_types = 1);

use App\Services\Recalculator\Queue\Jobs\CustomersRecalculator;
use App\Services\Recalculator\Queue\Jobs\ResellersRecalculator;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

return new class() extends RawDataMigration {
    protected function runRawUp(): void {
        $this->getContainer()->make(ResellersRecalculator::class)->dispatch();
        $this->getContainer()->make(CustomersRecalculator::class)->dispatch();
    }
};
