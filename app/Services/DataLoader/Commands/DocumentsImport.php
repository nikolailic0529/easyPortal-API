<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\DocumentsImporter;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsImport<DocumentsImporter>
 */
class DocumentsImport extends ObjectsImport {
    public function __invoke(Formatter $formatter, DocumentsImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
