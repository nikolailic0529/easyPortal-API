<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Services\DataLoader\Importer\Importers\Assets\AbstractImporterState;

class AssetsImporterState extends AbstractImporterState {
    public string $resellerId;
}
