<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Importer\ImporterState;

class AssetsImporterState extends ImporterState {
    public bool $withDocuments = true;
}
