<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importers\DistributorsImporter;

class ImportDistributors extends Import {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-import-distributors',
            '${objects}' => 'distributors',
            '${object}'  => 'distributor',
        ];
    }

    public function handle(DistributorsImporter $importer): int {
        return $this->process($importer);
    }
}
