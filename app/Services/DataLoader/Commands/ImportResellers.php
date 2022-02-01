<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importer\Importers\ResellersImporter;

class ImportResellers extends Import {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-import-resellers',
            '${objects}' => 'resellers',
            '${object}'  => 'reseller',
        ];
    }

    public function handle(ResellersImporter $importer): int {
        return $this->process($importer);
    }
}
