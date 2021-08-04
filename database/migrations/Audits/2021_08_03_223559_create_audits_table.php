<?php declare(strict_types = 1);

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use LastDragon_ru\LaraASP\Migrator\Migrations\RawMigration;

class CreateAuditsTables extends RawMigration {
    /**
     * The name of the database connection to use.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $connection = 'audits';

    // Please see the associated SQL files
}
