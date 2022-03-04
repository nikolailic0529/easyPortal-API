<?php declare(strict_types = 1);

namespace App\Services\Search\Migrations;

use App\Services\Search\Commands\IndexesRebuild;
use Illuminate\Contracts\Console\Kernel;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class RebuildIndexes extends RawDataMigration {
    protected function runRawUp(): void {
        $this->getContainer()->make(Kernel::class)->call(IndexesRebuild::class);
    }
}
