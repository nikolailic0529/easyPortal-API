<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Synchronizer\Synchronizers\DocumentsSynchronizer;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsSync<DocumentsSynchronizer>
 */
class DocumentsSync extends ObjectsSync {
    public function __invoke(Formatter $formatter, DocumentsSynchronizer $importer): int {
        return $this->process($formatter, $importer);
    }
}
