<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

use App\Services\DataLoader\Processors\Importer\Importers\Documents\BaseImporterState;

class DocumentsImporterState extends BaseImporterState {
    public string $resellerId;
}
