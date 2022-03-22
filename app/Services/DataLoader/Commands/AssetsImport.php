<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsImport<AssetsImporter>
 */
class AssetsImport extends ObjectsImport {
    public function __invoke(Formatter $formatter, AssetsImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
