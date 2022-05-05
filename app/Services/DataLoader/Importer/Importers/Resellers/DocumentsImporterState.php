<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Services\DataLoader\Importer\Importers\Documents\AbstractImporterState;

class DocumentsImporterState extends AbstractImporterState {
    public string $resellerId;
}
