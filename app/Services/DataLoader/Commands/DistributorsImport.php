<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\DistributorsImporter;
use App\Services\I18n\Formatter;

class DistributorsImport extends Import {
    public function __invoke(Formatter $formatter, DistributorsImporter $importer): int {
        return $this->process($formatter, $importer);
    }
}
