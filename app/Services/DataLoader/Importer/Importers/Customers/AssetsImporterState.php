<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Services\DataLoader\Importer\Importers\Assets\BaseImporterState;

class AssetsImporterState extends BaseImporterState {
    public string $customerId;
}
