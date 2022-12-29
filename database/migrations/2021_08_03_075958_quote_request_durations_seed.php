<?php declare(strict_types = 1);

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Database\Seeders\QuoteRequestDurationSeeder;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;

class QuoteRequestDurationsSeed extends RawDataMigration {
    protected function runRawUp(): void {
        Container::getInstance()->make(QuoteRequestDurationSeeder::class)->run();
    }
}
