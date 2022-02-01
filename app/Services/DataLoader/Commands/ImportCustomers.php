<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\CustomersImporter;

class ImportCustomers extends Import {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-import-customers',
            '${objects}' => 'customers',
            '${object}'  => 'customer',
        ];
    }

    public function handle(CustomersImporter $importer): int {
        return $this->process($importer);
    }
}
