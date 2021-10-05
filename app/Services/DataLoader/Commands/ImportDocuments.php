<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Importers\DocumentsImporter;

class ImportDocuments extends Import {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-import-documents',
            '${objects}' => 'documents',
            '${object}'  => 'document',
        ];
    }

    public function handle(DocumentsImporter $importer): int {
        return $this->process($importer);
    }
}
