<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectUpdate<DocumentLoader>
 */
class DocumentUpdate extends ObjectUpdate {
    public function __invoke(Formatter $formatter, DocumentLoader $loader): int {
        return $this->process($formatter, $loader);
    }
}
