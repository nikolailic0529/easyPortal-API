<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\DocumentsSynchronizer;

/**
 * @extends ObjectsSync<DocumentsSynchronizer>
 */
class DocumentsSync extends ObjectsSync {
    public function __invoke(DocumentsSynchronizer $importer): int {
        return $this->process($importer);
    }
}
