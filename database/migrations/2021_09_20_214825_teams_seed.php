<?php declare(strict_types = 1);

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Database\Seeders\TeamSeeder;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class TeamsSeed extends RawDataMigration {
    protected function runRawUp(): void {
        $this->getContainer()->make(TeamSeeder::class)->run();
    }
}
