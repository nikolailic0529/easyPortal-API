<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\Documents\Importer;
use App\Services\I18n\Formatter;

/**
 * @template TImporter of Importer<\App\Services\DataLoader\Importer\Importers\Documents\AbstractImporterState>
 *
 * @extends ObjectsImport<Importer>
 */
class DocumentsImport extends ObjectsImport {
    /**
     * @param TImporter $importer
     */
    public function __invoke(Formatter $formatter, Importer $importer): int {
        return $this->process($formatter, $importer);
    }
}
