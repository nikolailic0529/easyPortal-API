<?php declare(strict_types = 1);

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use App\Services\Logger\Logger;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawMigration;

class AlterLogsConvertDurationIntoDouble extends RawMigration {
    /**
     * The name of the database connection to use.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $connection = Logger::CONNECTION;

    // Please see the associated SQL files
}
