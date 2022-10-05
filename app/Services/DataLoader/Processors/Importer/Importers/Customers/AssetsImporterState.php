<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Customers;

use App\Services\DataLoader\Processors\Importer\Importers\Assets\BaseImporterState;

class AssetsImporterState extends BaseImporterState {
    public string $customerId;
}
