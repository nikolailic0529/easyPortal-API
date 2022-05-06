<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\Documents\Importer;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsImport<Importer>
 */
class DocumentsImport extends ObjectsImport {
    public function __invoke(Formatter $formatter, Importer $importer): int {
        return $this->process($formatter, $importer);
    }
}
