<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\DocumentsImporter;
use App\Services\I18n\Formatter;

/**
 * @template TImporter of DocumentsImporter<\App\Services\DataLoader\Importer\Importers\DocumentsImporterState>
 *
 * @extends ObjectsImport<DocumentsImporter>
 */
class DocumentsImport extends ObjectsImport {
    /**
     * @param TImporter $importer
     */
    public function __invoke(Formatter $formatter, DocumentsImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
