<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\CustomersImporter;
use App\Services\I18n\Formatter;

class CustomersImport extends ObjectsImport {
    public function __invoke(Formatter $formatter, CustomersImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
