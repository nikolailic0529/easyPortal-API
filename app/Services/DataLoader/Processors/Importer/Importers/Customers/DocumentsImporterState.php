<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Customers;

use App\Services\DataLoader\Processors\Importer\Importers\Documents\BaseImporterState;

class DocumentsImporterState extends BaseImporterState {
    public string $customerId;
}
