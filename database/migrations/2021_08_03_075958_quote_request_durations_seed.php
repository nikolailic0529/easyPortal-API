<?php declare(strict_types = 1);

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Database\Seeders\QuoteRequestDurationSeeder;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class QuoteRequestDurationsSeed extends RawDataMigration {
    protected function runRawUp(): void {
        $this->getContainer()->make(QuoteRequestDurationSeeder::class)->run();
    }
}
