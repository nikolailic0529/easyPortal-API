<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\ResellersImporter;
use App\Services\I18n\Formatter;

class ResellersImport extends ObjectsImport {
    public function __invoke(Formatter $formatter, ResellersImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
