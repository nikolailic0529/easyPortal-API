<?php declare(strict_types = 1);

use App\Services\Audit\Auditor;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawMigration;

return new class() extends RawMigration {
    // Please see the associated SQL files
    /**
     * The name of the database connection to use.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $connection = Auditor::CONNECTION;
};
