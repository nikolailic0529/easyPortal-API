<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\DistributorsImporter;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsImport<DistributorsImporter>
 */
class DistributorsImport extends ObjectsImport {
    public function __invoke(Formatter $formatter, DistributorsImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
