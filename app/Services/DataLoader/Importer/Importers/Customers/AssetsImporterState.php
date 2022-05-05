<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Services\DataLoader\Importer\Importers\Assets\AbstractImporterState;

class AssetsImporterState extends AbstractImporterState {
    public string $customerId;
}
