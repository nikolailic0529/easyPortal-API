<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\OemsImporter;

use LastDragon_ru\LaraASP\Core\Enum;

class CellType extends Enum {
    public static function text(): static {
        return static::make(__FUNCTION__);
    }
}
