<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importers\AssetsImporter;

class ImportAssets extends Import {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-import-assets',
            '${objects}' => 'assets',
            '${object}'  => 'asset',
        ];
    }

    public function handle(AssetsImporter $importer): int {
        return $this->process($importer);
    }
}
